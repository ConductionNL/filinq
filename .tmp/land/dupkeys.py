import json, sys
found=[]
def hook(pairs):
    seen={}
    for k,v in pairs:
        if k in seen: found.append((k, str(seen[k])[:80], str(v)[:80]))
        seen[k]=v
    return dict(pairs)
for f in sys.argv[1:]:
    found.clear()
    json.load(open(f,encoding='utf-8'), object_pairs_hook=hook)
    print(f, 'duplicate keys:', len(found))
    for x in found: print('  ', x)
