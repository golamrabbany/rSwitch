"""
Phone-number normalization helpers for rating.

Kept dependency-free (only the stdlib) so the pure-logic units are testable
without sqlalchemy/redis/etc.
"""

import re


# BD Mobile Number Portability: operator prefix -> MNP route code. Keyed on the
# two leading national digits ('1' mobile indicator + operator digit).
BD_MNP_MAP = {
    "13": "71",  # Grameenphone
    "14": "91",  # Banglalink
    "15": "51",  # Teletalk
    "16": "81",  # Airtel
    "17": "71",  # Grameenphone
    "18": "81",  # Robi
    "19": "91",  # Banglalink
}


def bd_national(number: str) -> str:
    """Return the Bangladesh national subscriber digits for `number`.

    Strips, in order: every non-digit character (spaces, dashes, a leading '+'),
    then the country code (`880`, or the IDD form `00880`) or a single national
    trunk `0`.

    Separators are removed FIRST so a number written with a grouping dash —
    e.g. '8801730-032367' — still yields a clean 10-digit national part
    ('1730032367') instead of failing the length check downstream. That stray
    dash was leaving valid numbers un-converted, so the carrier answered them
    with '503 No Treatment Reached'.

      * "8801714101351"  -> "1714101351"
      * "01714101351"    -> "1714101351"
      * "8801730-032367" -> "1730032367"
      * "008801714101351"-> "1714101351"
      * ""               -> ""
    """
    if not number:
        return ""
    n = re.sub(r"\D", "", str(number))
    if n.startswith("00880"):
        return n[5:]
    if n.startswith("880"):
        return n[3:]
    if n.startswith("0"):
        return n[1:]
    return n


def apply_bd_mnp(number: str) -> str:
    """Convert a BD mobile number to its MNP dialing form
    (880 + route_code + national). Non-BD, non-mobile, or malformed numbers pass
    through UNCHANGED — the converter never mangles input; rejecting unroutable
    numbers is a separate policy decision (see ``is_malformed_bd_mobile``).

      * "8801714101351"  -> "880711714101351"
      * "01714101351"    -> "880711714101351"
      * "8801730-032367" -> "880711730032367"  (stray dash tolerated)
      * "0255012345"     -> "0255012345"        (landline, not a mobile)
      * "880175440173"   -> "880175440173"      (malformed, left as-is)
    """
    national = bd_national(number)
    if len(national) != 10:
        return number
    mnp_code = BD_MNP_MAP.get(national[:2])
    if not mnp_code:
        return number  # not a BD mobile operator -> passthrough
    return "880" + mnp_code + national


def is_malformed_bd_mobile(number: str) -> bool:
    """True when `number` is clearly a Bangladesh mobile destination — its
    national part starts with a known operator prefix (13–19) — but has an
    invalid length, so it cannot be MNP-converted.

    Such numbers are unroutable: the carrier answers '503 No Treatment Reached'
    because there is no MNP prefix it can route on. Rejecting them at the switch
    keeps junk off the trunk (protecting ASR) and records an auditable FAILED
    CDR. Valid mobile numbers, and any non-BD / non-mobile number, return False
    (operator prefix absent from BD_MNP_MAP), so legitimate international traffic
    is never rejected.
    """
    national = bd_national(number)
    return national[:2] in BD_MNP_MAP and len(national) != 10


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
