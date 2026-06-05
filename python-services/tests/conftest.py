import os
import sys

# Make the engine package importable as `shared.*`, `monitoring.*`, etc.
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
