import json
import os

_cache = None

def load() -> dict:
    global _cache
    if _cache is None:
        path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "identity.json")
        with open(path, encoding="utf-8") as f:
            _cache = json.load(f)
    return _cache

def get(key, default=None):
    return load().get(key, default)
