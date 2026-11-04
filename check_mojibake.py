import glob

vue_files = glob.glob(r'E:\bushub-booking\resources\js\**\*.vue', recursive=True)
bad_markers = ['ÄĂ', 'â‡„', 'đŸ', 'âš', 'PhĂ²ng', 'chá»—', 'giĂ¡', 'Ä Ă', 'Báº¿n', 'Tuyáº¿n', 'chuyáº¿n']

found_corrupt = {}
for f in vue_files:
    try:
        content = open(f, 'r', encoding='utf-8').read()
        corrupt_lines = [l.strip() for l in content.split('\n') if any(m in l for m in bad_markers)]
        if corrupt_lines:
            found_corrupt[f] = corrupt_lines[:3]
    except Exception as e:
        print(f"Error {f}: {e}")

for f, samples in found_corrupt.items():
    print(f"=== {f} ({len(samples)} samples) ===")
    for s in samples:
        print("  ", s)
