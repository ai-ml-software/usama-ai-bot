"""Unit tests: no accidental paid model execution."""
import sys
import types
import unittest
from pathlib import Path
from unittest.mock import AsyncMock, patch
import importlib.util

pkg = types.ModuleType('bot'); pkg.__path__ = [str(Path(__file__).parent)]; sys.modules['bot']=pkg
cfg = types.ModuleType('bot.config'); cfg.Config = type('Config', (), {'AI_API_KEY':'sk_test'})
sys.modules['bot.config']=cfg
spec=importlib.util.spec_from_file_location('bot.free_media',Path(__file__).with_name('free_media.py'))
m=importlib.util.module_from_spec(spec);sys.modules['bot.free_media']=m;spec.loader.exec_module(m)

class MediaTests(unittest.IsolatedAsyncioTestCase):
    def test_prices(self):
        self.assertTrue(m._price_is_explicitly_zero({'pricing':{'per_image':0,'usd':0}}))
        self.assertFalse(m._price_is_explicitly_zero({'pricing':{'per_second':0.02}}))
        self.assertFalse(m._price_is_explicitly_zero({'pricing':{}}))
        self.assertFalse(m._price_is_explicitly_zero({'pricing':{'per_image':0, 'per_second':0.04}}))
        self.assertFalse(m._price_is_explicitly_zero({'id':'mystery'}))
    def test_catalog(self):
        self.assertEqual(m._candidates({'data':[{'id':'free','pricing':{'per_image':0}}, {'id':'paid','pricing':{'per_image':0.3}}, {'id':'unknown'}]},'image'), ['free'])
    async def test_no_free_no_call(self):
        with patch.object(m,'verified_models',new=AsyncMock(return_value=[])):
            with self.assertRaises(m.NoVerifiedFreeModel):
                await m.generate_media('video','test')
    async def test_auth_required(self):
        cfg.Config.AI_API_KEY='anonymous'
        with patch.object(m,'verified_models',new=AsyncMock(return_value=['free'])):
            with self.assertRaises(m.NoVerifiedFreeModel):
                await m.generate_media('image','test')
        cfg.Config.AI_API_KEY='sk_test'

if __name__=='__main__': unittest.main()
