from copy import deepcopy
from datetime import datetime, timezone
import json

import pytest

from terminal.operations import Operations
from terminal.portfolio import Ledger, load_config


@pytest.fixture
def workspace(tmp_path):
    ledger = Ledger(tmp_path / 'operations.sqlite3')
    ledger.create('original', 'demo', 100000)
    cfg = load_config()
    market = {'symbol': 'AAPL', 'price': 100., 'asof': datetime.now(timezone.utc).isoformat(), 'source': 'demo'}
    ledger.execute('original', 'BUY', 10, market, {}, cfg, 'buy', 'Initial position')
    ledger.execute('original', 'SELL', 3, {**market, 'price': 110.}, {'AAPL': 110.}, cfg, 'sell', 'Partial profit')
    ledger.mark('original', {'AAPL': 110.}, cfg)
    ops = Operations(ledger)
    ops.save_preferences('original', ['AAPL', 'MSFT'], 15)
    return ledger, ops


def test_backup_recovery_reconciles_and_preserves_original(workspace):
    ledger, ops = workspace
    backup = ops.backup('original')
    assert 'id' not in backup['payload']['account']
    new_id = ops.restore(json.loads(json.dumps(backup)), 'demo')
    old, old_pos = ledger.read('original')
    new, new_pos = Ledger(ledger.path).read(new_id)
    assert new['cash'] == old['cash']
    assert new['paused'] == 1 and old['paused'] == 0
    assert new_pos['AAPL']['qty'] == old_pos['AAPL']['qty'] == 7
    assert new_pos['AAPL']['entry'] == old_pos['AAPL']['entry']
    assert len(ledger.trades(new_id)) == 2
    assert ops.preferences(new_id, {}) == {'symbols': ['AAPL', 'MSFT'], 'scan_interval': 15}


def test_checksum_rejects_corruption(workspace):
    ledger, ops = workspace
    backup = ops.backup('original')
    backup['payload']['account']['cash'] += 100
    with pytest.raises(ValueError, match='checksum'):
        ops.restore(backup, 'demo')
    with ledger.connection() as db:
        assert db.execute('SELECT COUNT(*) FROM accounts').fetchone()[0] == 1


@pytest.mark.parametrize('field', ['cash', 'qty', 'entry', 'realized', 'missing', 'nan', 'snapshots'])
def test_reconciliation_rejects_invalid_backups(workspace, field):
    ledger, ops = workspace
    backup = deepcopy(ops.backup('original'))
    p = backup['payload']
    if field == 'cash': p['account']['cash'] += 50
    elif field == 'qty': p['positions'][0]['qty'] = 999
    elif field == 'entry': p['positions'][0]['entry'] *= 2
    elif field == 'realized': p['trades'][1]['realized'] += 5
    elif field == 'missing': p['positions'] = []
    elif field == 'nan': p['account']['cash'] = float('nan')
    elif field == 'snapshots': p['snapshots'].append(p['snapshots'][0])
    if field != 'nan': backup['checksum'] = ops.checksum(p)
    with pytest.raises(ValueError): ops.restore(backup, 'demo')
    with ledger.connection() as db:
        assert db.execute('SELECT COUNT(*) FROM accounts').fetchone()[0] == 1


def test_mode_mismatch_rejected(workspace):
    _, ops = workspace
    with pytest.raises(ValueError, match='workspace mode'):
        ops.restore(ops.backup('original'), 'yahoo')


def test_scan_audit_and_preferences_account_isolation(workspace):
    ledger, ops = workspace
    ledger.create('other', 'demo', 100000)
    cfg = load_config()
    scan_id = ops.record_scan('original', 'demo', cfg, [{'symbol': 'AAPL', 'action': 'HOLD'}, {'symbol': 'MSFT', 'action': 'UNAVAILABLE', 'error': 'Provider timeout'}])
    assert Operations(Ledger(ledger.path)).scans('original')[0]['id'] == scan_id
    assert ops.scans('original')[0]['config'] == cfg
    assert ops.scans('other') == []
    assert ops.preferences('other', {'symbols': ['NVDA']}) == {'symbols': ['NVDA']}
    with pytest.raises(ValueError): ops.save_preferences('original', ['../../'], 15)
    with pytest.raises(ValueError): ops.save_preferences('original', ['AAPL'], 2)
