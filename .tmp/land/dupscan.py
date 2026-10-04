import json, sys
from collections import Counter
def walk(node, path, out):
    if isinstance(node, list):
        dicts=[x for x in node if isinstance(x, dict)]
        for key in ('id','uuid'):
            vals=[d.get(key) for d in dicts if isinstance(d.get(key),(str,int))]
            vals+= [d['@self'].get(key) for d in dicts if isinstance(d.get('@self'),dict) and isinstance(d['@self'].get(key),(str,int))]
            for v,c in Counter(vals).items():
                if c>1: out.append(f"DUP {key}={v} x{c} at {path}")
        if path.endswith('recipients'):
            norm=[json.dumps(x,sort_keys=True).lower().replace(' ','') for x in node]
            for v,c in Counter(norm).items():
                if c>1: out.append(f"DUP recipient {v} at {path}")
        for i,x in enumerate(node): walk(x, f"{path}[{i}]", out)
    elif isinstance(node, dict):
        for k,v in node.items(): walk(v, f"{path}.{k}", out)
for f in sys.argv[1:]:
    out=[]; walk(json.load(open(f,encoding='utf-8')), '$', out)
    print(f, 'findings:', len(out))
    for o in out[:20]: print('  ', o)
