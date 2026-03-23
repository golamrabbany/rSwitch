"""
Prefix Trie for O(n) longest-prefix-match rate lookups.

Replaces the PHP SQL approach:
    WHERE prefix IN ('8801712', '880171', '88017', '8801', '880', '88', '8')
    ORDER BY LENGTH(prefix) DESC

With an in-memory trie walk that finds the match in O(n) where n = digits.
"""

from __future__ import annotations
from typing import Optional, Any


class TrieNode:
    __slots__ = ["children", "rate"]

    def __init__(self):
        self.children: dict[str, TrieNode] = {}
        self.rate: Optional[Any] = None


class PrefixTrie:
    """
    A trie (prefix tree) optimized for telephone number prefix matching.

    Usage:
        trie = PrefixTrie()
        trie.insert("880", rate_880)
        trie.insert("88017", rate_88017)

        match = trie.longest_match("8801712345")
        # Returns rate_88017 (longest matching prefix)
    """

    def __init__(self):
        self.root = TrieNode()
        self._size = 0

    def insert(self, prefix: str, rate) -> None:
        """Insert a rate keyed by its prefix."""
        node = self.root
        for digit in prefix:
            if digit not in node.children:
                node.children[digit] = TrieNode()
            node = node.children[digit]
        node.rate = rate
        self._size += 1

    def longest_match(self, number: str) -> Optional[Any]:
        """
        Walk the trie digit-by-digit, tracking the deepest node that has a rate.
        Returns the rate with the longest matching prefix, or None.
        """
        node = self.root
        best_rate = None

        for digit in number:
            if digit not in node.children:
                break
            node = node.children[digit]
            if node.rate is not None:
                best_rate = node.rate

        return best_rate

    def __len__(self) -> int:
        return self._size

    def __bool__(self) -> bool:
        return self._size > 0
