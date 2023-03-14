# 2Q Cache Full Version

[![Coverage Status](https://coveralls.io/repos/github/sokil/php-2q/badge.svg?branch=main)](https://coveralls.io/github/sokil/php-2q?branch=main)

The Q2 cache algorithm is a caching algorithm that aims to balance between frequently accessed
and infrequently accessed items in a cache. It works by dividing the cache into three buffers:
a frequently accessed buffer (in), a moderately accessed buffer (out), and an infrequently accessed buffer (main).
The "in" is the smallest buffer and contains the most frequently accessed items.
The "out" buffer is larger and contains items that are accessed less frequently than those in the "in"
but more frequently than those in the "main".
The "main" is the largest buffer and contains items that are rarely accessed.

See full description in https://www.vldb.org/conf/1994/P439.PDF
