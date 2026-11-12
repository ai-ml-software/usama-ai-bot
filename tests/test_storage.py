"""Tests that do not require AWS/HF credentials."""
import os
import unittest
from unittest.mock import patch
from bot.storage import key_for, get_client

class StorageTest(unittest.TestCase):
    def test_safe_keys(self):
        self.assertEqual(key_for('hello.txt'), 'telegram-bot/hello.txt')
        for bad in ('', '.', '..', '../secret', 'dir/name', r'dir\name'):
            with self.subTest(bad=bad), self.assertRaises(ValueError):
                key_for(bad)

    def test_no_credentials_fails_closed(self):
        with patch.dict(os.environ, {'AWS_ACCESS_KEY_ID': '', 'AWS_SECRET_ACCESS_KEY': ''}):
            with self.assertRaises(RuntimeError):
                get_client()

if __name__ == '__main__':
    unittest.main()
