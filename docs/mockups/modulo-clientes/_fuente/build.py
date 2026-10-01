import json, os, re, sys

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(HERE, '..')
shell = open(os.path.join(HERE, 'shell.html'), encoding='utf-8').read()
kit = open(os.path.join(HERE, 'kit.js'), encoding='utf-8').read().rstrip()


def parts(text):
    out = {}
    for m in re.finditer(r'^---(\w+)---\n(.*?)(?=^---\w+---\n|\Z)', text, re.S | re.M):
        out[m.group(1)] = m.group(2).rstrip('\n')
    return out


names = sys.argv[1:] or [f[:-5] for f in sorted(os.listdir(os.path.join(HERE, 'pages'))) if f.endswith('.page')]
for name in names:
    p = parts(open(os.path.join(HERE, 'pages', name + '.page'), encoding='utf-8').read())
    meta = json.loads(p['META'])
    html = shell
    for key, val in [
        ('%%TITLE%%', meta['title']), ('%%H%%', str(meta['h'])), ('%%MAXW%%', str(meta.get('maxw', 1280))),
        ('%%CTX%%', meta['ctx']), ('%%STATE%%', meta.get('state', '{}')),
        ('%%CONTENT%%', p['CONTENT']), ('%%OVERLAY%%', p.get('OVERLAY', '')), ('%%VALS%%', p['VALS']), ('%%KIT%%', kit),
    ]:
        assert key in html, key
        html = html.replace(key, val)
    open(os.path.join(OUT, name + '.dc.html'), 'w', encoding='utf-8').write(html)
    print('built', name)
