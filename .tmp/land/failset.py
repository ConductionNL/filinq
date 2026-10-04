import sys, xml.etree.ElementTree as ET
t = ET.parse(sys.argv[1])
out = []
for tc in t.iter('testcase'):
    for tag in ('failure', 'error'):
        if tc.find(tag) is not None:
            out.append(f"{tc.get('class')}::{tc.get('name')} [{tag}]")
for x in sorted(out): print(x)
