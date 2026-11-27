import sys, json, os, re
# Genera Doc/qa/paridad.md desde rows.json (salida de paridad.py)
sys.stdout.reconfigure(encoding='utf-8')
d, dest, commit = sys.argv[1], sys.argv[2], sys.argv[3]
data = json.load(open(os.path.join(d, 'rows.json'), encoding='utf-8'))
ROUTES = [x['name'] for x in json.load(open(os.path.join(d, 'routes.json'), encoding='utf-8')) if x.get('name')]
def route_hit(r):
    h = r['header']
    if 'Ruta Laravel' not in h: return None
    i = h.index('Ruta Laravel')
    if i >= len(r['cells']): return None
    for tok in re.findall(r'`([^`]+)`', r['cells'][i]):
        first = tok.split('/')[0].strip()
        for n in ROUTES:
            if n.endswith('promocion.' + first):
                return n
    return None
META = {
    'nucleo-base': ('Núcleo y 基本情報管理 (category0)', 'S1'),
    'clientes-ventas-reservas': ('顧客管理, 売上 y 予約 (category1, ventas, category9)', 'S2'),
    'promocion': ('販売促進管理 (category3)', 'S3'),
    'contenido-terminales': ('マイページ, HP y 端末管理 (category6, category7)', 'S4'),
    'operaciones-integraciones': ('業務支援管理 e integraciones (category5, API)', 'S5'),
}
ORDER = ['nucleo-base', 'clientes-ventas-reservas', 'promocion', 'contenido-terminales', 'operaciones-integraciones']
clean = lambda s: re.sub(r'\s+', ' ', s).strip()
def cnt(rows):
    c = {'hecho': 0, 'pendiente': 0, 'descartado': 0, 'otro': 0}
    for r in rows: c[r['k']] += 1
    return c
out = []
out.append('# Matriz de paridad por módulo\n')
out.append(f'S1, 2026-11-27. Resumen de las tablas de paridad de `Doc/diseno/*.md` en `laravel13` ({commit}): cada fila con columna **Estado** cuenta una vez (una pantalla, endpoint o script del legacy). "hecho" incluye los que llevan nota (p. ej. "hecho (falta verlo con el layout)"); "pendiente" incluye los ⏸ (bloqueados o en espera de decisión). Se regenera con el script de S1 (`paridad.py`, ver al final).\n')
out.append('## Resumen\n')
out.append('| Módulo | Sesión | Hecho | Pendiente | Descartado | Otro | Total | % hecho (sin descartados) |')
out.append('|---|---|---:|---:|---:|---:|---:|---:|')
tot = {'hecho': 0, 'pendiente': 0, 'descartado': 0, 'otro': 0}
for n in ORDER:
    c = cnt(data[n]); total = sum(c.values()); vivos = total - c['descartado']
    for k in tot: tot[k] += c[k]
    pct = f"{100 * c['hecho'] / vivos:.0f} %" if vivos else '—'
    out.append(f"| [{META[n][0]}](../diseno/{n}.md) | {META[n][1]} | {c['hecho']} | {c['pendiente']} | {c['descartado']} | {c['otro']} | {total} | {pct} |")
T = sum(tot.values()); V = T - tot['descartado']
out.append(f"| **Total** | | **{tot['hecho']}** | **{tot['pendiente']}** | **{tot['descartado']}** | **{tot['otro']}** | **{T}** | **{100 * tot['hecho'] / V:.0f} %** |\n")
for n in ORDER:
    rows = data[n]
    out.append(f"## {META[n][0]} — {META[n][1]}\n")
    if n == 'promocion' and cnt(rows)['hecho'] == 0:
        vivos = [r for r in rows if r['k'] != 'descartado']
        hits = [r for r in vivos if route_hit(r)]
        out.append(f"> **Ojo:** la columna Estado de `promocion.md` no está al día (todo figura como pendiente; el avance real está en su registro del final: F1, F2…). Cruzando su columna «Ruta Laravel» con las rutas que existen hoy en el código, **{len(hits)} de {len(vivos)}** filas no descartadas ya tienen ruta (marcadas abajo con «ruta ya existe»). S3 debe actualizar la columna para el cierre; hasta entonces el % de promoción no es real.\n")
    secs = {}
    for r in rows:
        key = clean(r['sec']) + (' › ' + clean(r['sub']) if r['sub'] else '')
        secs.setdefault(key, []).append(r)
    out.append('| Sección | Hecho | Pendiente | Descartado | Otro |')
    out.append('|---|---:|---:|---:|---:|')
    for key, rs in secs.items():
        c = cnt(rs)
        out.append(f"| {key} | {c['hecho']} | {c['pendiente']} | {c['descartado']} | {c['otro']} |")
    out.append('')
    pend = [r for r in rows if r['k'] in ('pendiente', 'otro')]
    if pend:
        out.append(f"**Pendientes ({sum(1 for r in pend if r['k']=='pendiente')})" + (f" y estado sin clasificar ({sum(1 for r in pend if r['k']=='otro')})" if any(r['k']=='otro' for r in pend) else '') + ':**\n')
        last = None
        for r in pend:
            key = clean(r['sec']) + (' › ' + clean(r['sub']) if r['sub'] else '')
            if key != last:
                out.append(f"- _{key}_")
                last = key
            c = r['cells']
            what = clean(c[0])[:110]
            screen = clean(c[1])[:60] if len(c) > 2 and c[1] not in ('', '—') and c[1] != r['estado'] else ''
            st = clean(r['estado'])
            mark = '' if st.lower() == 'pendiente' else f" — {st[:90]}"
            hit = route_hit(r) if n == 'promocion' else None
            out.append(f"  - {what}" + (f" ({screen})" if screen else '') + mark + (f" — **ruta ya existe** (`{hit}`)" if hit else ''))
        out.append('')
    else:
        out.append('Sin pendientes.\n')
out.append('## Cómo se obtuvo\n')
out.append('`git show origin/laravel13:Doc/diseno/<doc>.md` de los cinco documentos y un script que recorre cada tabla Markdown cuya cabecera tiene la columna `Estado`, toma el título `##`/`###` como sección y clasifica la celda por su primera palabra (`hecho…`, `pendiente…`/`⏸…`, `descartado…`; el resto, "otro"). Las filas de otras tablas (contratos, decisiones, pruebas) no cuentan.\n')
open(dest, 'w', encoding='utf-8').write('\n'.join(out) + '\n')
print('ok', tot)
