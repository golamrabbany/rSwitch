"""
Phone-number normalization helpers for rating.

Kept dependency-free (only the stdlib) so the pure-logic units are testable
without sqlalchemy/redis/etc.
"""


def normalize_bd_msisdn(number: str) -> str:
    """Normalize a Bangladesh national-format MSISDN to international (E.164
    without the leading +), so it matches 880-prefixed rate rows.

    Customers dial national format (e.g. 01714101351) but rate tables store
    the international prefix (8801714101351). Longest-prefix matching against
    an `880` rate fails for a `01...` number, which leaves the call unbillable.

    Rules:
      * "01714101351" -> "8801714101351"  (strip single leading 0, prepend 880)
      * "0255012345"  -> "880255012345"   (landline, same rule)
      * "8801714101351" -> unchanged       (already international)
      * "008801714101351" -> unchanged     (IDD access code, not a national no.)
      * "1714101351"  -> unchanged          (no trunk prefix to strip)
      * ""            -> ""                  (empty passthrough)
    """
    if not number:
        return number

    n = number.strip()

    # Already international, or dialed with an IDD (00) access code → leave it.
    if n.startswith("880") or n.startswith("00"):
        return n

    # National format: a single leading trunk 0 followed by the subscriber number.
    if n.startswith("0") and len(n) >= 2:
        return "880" + n[1:]

    return n
