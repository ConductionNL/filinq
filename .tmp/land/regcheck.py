import json,subprocess,sys,collections
p='lib/Settings/filinq_register.json'
dev=json.loads(subprocess.check_output(['git','show','origin/development:'+p]))
dups=[]
def hook(pairs):
    c=collections.Counter(k for k,_ in pairs)
    dups.extend(k for k,n in c.items() if n>1)
    return dict(pairs)
cur=json.load(open(p),object_pairs_hook=hook)
print('info.version dev',dev['info']['version'],'branch',cur['info']['version'])
print('duplicate keys',dups)
from packaging.version import Version as V
for s,d in cur['components']['schemas'].items():
    dd=dev['components']['schemas'].get(s)
    if dd is None: print('NEW schema',s,d.get('version')); continue
    if dd!=d:
        ok=V(d.get('version','0'))>V(dd.get('version','0'))
        print('changed',s,dd.get('version'),'->',d.get('version'),'OK' if ok else 'NOT ABOVE')
def scan(o,path):
    if isinstance(o,list):
        for k in ('id','uuid'):
            vals=[x.get(k) for x in o if isinstance(x,dict) and x.get(k) is not None]
            rep=[v for v,n in collections.Counter(map(str,vals)).items() if n>1]
            if rep: print('repeated',k,path,rep[:5])
        for i,x in enumerate(o): scan(x,path+f'[{i}]')
    elif isinstance(o,dict):
        for k,v in o.items(): scan(v,path+'.'+k)
scan(cur,'$')
