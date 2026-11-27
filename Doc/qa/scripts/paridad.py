import sys, re, json, os
import sys; sys.stdout.reconfigure(encoding="utf-8")
# Recorre las tablas con columna "Estado" de los Doc/diseno/*.md y clasifica cada fila
d = sys.argv[1]
out = {}
for name in ['nucleo-base', 'clientes-ventas-reservas', 'promocion', 'operaciones-integraciones', 'contenido-terminales']:
    lines = open(os.path.join(d, name + '.md'), encoding='utf-8').read().splitlines()
    section, sub = '', ''
    header = None
    rows = []
    for ln in lines:
        if ln.startswith('#'):
            lvl = len(ln) - len(ln.lstrip('#'))
            t = ln.lstrip('#').strip()
            if lvl <= 2: section, sub = t, ''
            else: sub = t
            header = None
            continue
        if not ln.startswith('|'):
            header = None
            continue
        cells = [c.strip() for c in ln.strip().strip('|').split('|')]
        if header is None:
            header = cells
            continue
        if all(re.fullmatch(r':?-+:?', c or '-') for c in cells):
            continue
        if 'Estado' not in header:
            continue
        i = header.index('Estado')
        if i >= len(cells): continue
        st = cells[i]
        low = st.lower()
        if low.startswith('hecho'): k = 'hecho'
        elif low.startswith('pendiente') or st.startswith('⏸'): k = 'pendiente'
        elif low.startswith('descartado'): k = 'descartado'
        else: k = 'otro'
        rows.append({'sec': section, 'sub': sub, 'cells': cells, 'header': header, 'estado': st, 'k': k})
    out[name] = rows
json.dump(out, open(os.path.join(d, 'rows.json'), 'w', encoding='utf-8'), ensure_ascii=False)
for name, rows in out.items():
    c = {}
    for r in rows: c[r['k']] = c.get(r['k'], 0) + 1
    print(name, c)
    secs = {}
    for r in rows:
        key = r['sec'] + (' / ' + r['sub'] if r['sub'] else '')
        s = secs.setdefault(key, {})
        s[r['k']] = s.get(r['k'], 0) + 1
    for k, v in secs.items(): print('   ', k[:70], v)
    others = [r['estado'] for r in rows if r['k'] == 'otro']
    if others: print('    OTROS:', others[:8])
