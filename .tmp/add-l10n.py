import json,sys
pairs=json.load(open(sys.argv[1]))
for lang in ('en','nl'):
    path=f'l10n/{lang}.json'
    raw=open(path).read()
    d=json.loads(raw)
    t=d['translations']
    for en,nl in pairs.items():
        if en not in t:
            t[en]= en if lang=='en' else nl
    out=json.dumps(d,indent=4,ensure_ascii=False)+"\n"
    open(path,'w').write(out)
