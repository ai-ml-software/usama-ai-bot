"""Technical-only, next-bar-open backtest; no future news or same-close entries."""
import pandas as pd


def backtest(frame, cfg):
    cash = float(cfg["paper_cash"])
    qty, entry, stop, target = 0, 0., 0., 0.
    records, curve = [], []
    slip = cfg["slippage_bps"] / 10000
    fee = cfg["fee_per_order"]
    for i in range(50, len(frame)):
        prior, bar = frame.iloc[i - 1], frame.iloc[i]
        exited = False
        if qty:
            # Stop first when both levels are touched; adverse opening gaps fill at open.
            exit_price, why = None, None
            if bar.Open <= stop:
                exit_price, why = bar.Open, "Stop gap"
            elif bar.Open >= target:
                exit_price, why = bar.Open, "Target gap"
            elif prior.Close < prior.SMA20:
                exit_price, why = bar.Open, "Trend exit"
            elif bar.Low <= stop:
                exit_price, why = stop, "Stop"
            elif bar.High >= target:
                exit_price, why = target, "Target"
            if exit_price is not None:
                fill = exit_price * (1 - slip)
                pnl = (fill - entry) * qty - fee
                cash += qty * fill - fee
                records.append({"date": frame.index[i], "side": "SELL", "qty": qty, "fill": fill, "realized": pnl, "reason": why})
                qty, exited = 0, True
        if not qty and not exited and prior.Close > prior.SMA20 > prior.SMA50 and 35 <= prior.RSI <= 65 and prior.MACD > prior.MACD_signal:
            fill = bar.Open * (1 + slip)
            qty = max(0, int(min(cash * cfg["max_account_pct_per_position"] / 100 / fill, max(0, cash * cfg["risk_pct_per_trade"] / 100 - fee) / (fill * cfg["stop_pct"] / 100), (cash - fee) / fill)))
            if qty:
                cash -= qty * fill + fee
                entry = fill + fee / qty
                stop, target = entry * (1 - cfg["stop_pct"] / 100), entry * (1 + cfg["take_profit_pct"] / 100)
                records.append({"date": frame.index[i], "side": "BUY", "qty": qty, "fill": fill, "realized": 0., "reason": "Previous close rules"})
                if bar.Low <= stop or bar.High >= target:
                    exit_price = stop if bar.Low <= stop else target
                    why = "Entry-bar stop" if bar.Low <= stop else "Entry-bar target"
                    exit_fill = exit_price * (1 - slip)
                    pnl = (exit_fill - entry) * qty - fee
                    cash += qty * exit_fill - fee
                    records.append({"date": frame.index[i], "side": "SELL", "qty": qty, "fill": exit_fill, "realized": pnl, "reason": why})
                    qty = 0
        curve.append({"date": frame.index[i], "equity": cash + qty * bar.Close})
    result = pd.DataFrame(curve)
    result["benchmark"] = cfg["paper_cash"] * frame.Close.iloc[50:].to_numpy() / frame.Close.iloc[50]
    result["drawdown"] = result.equity / result.equity.cummax().clip(lower=cfg["paper_cash"]) - 1
    return result, pd.DataFrame(records)
