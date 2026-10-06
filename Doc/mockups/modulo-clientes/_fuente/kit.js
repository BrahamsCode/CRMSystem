function crmThemeKit() {
  const ACC = { coral: '#C8343A', azul: '#206BC4', indigo: '#264C73', ambar: '#B45309', verde: '#15803D', violeta: '#6D28D9', negro: '#18181B' };
  const ACC_DARK = { coral: '#F06A6F', azul: '#6AA5F0', indigo: '#8DB1DB', ambar: '#F0A54A', verde: '#4ADE80', violeta: '#B39BFA', negro: '#F4F4F5' };
  const ACC_NAME = { coral: 'Coral', azul: 'Azul', indigo: 'Índigo (ai-iro)', ambar: 'Ámbar', verde: 'Verde', violeta: 'Violeta', negro: 'Negro' };
  const PRESETS = {
    coral: { mode: 'light', accent: 'coral', layout: 'double', nav: 'dark', cards: 'border', radius: 'soft', density: 'comfy', font: 'Manrope', head: 'sans', paper: '#F5F6F8', surface: '#FFFFFF' },
    tabler: { mode: 'light', accent: 'azul', layout: 'top', nav: 'dark', cards: 'shadow', radius: 'square', density: 'comfy', font: 'Source Sans 3', head: 'sans', paper: '#F3F5F9', surface: '#FFFFFF' },
    filament: { mode: 'light', accent: 'ambar', layout: 'side', nav: 'light', cards: 'shadow', radius: 'round', density: 'comfy', font: 'Be Vietnam Pro', head: 'sans', paper: '#F8F9FA', surface: '#FFFFFF' },
    minimal: { mode: 'light', accent: 'negro', layout: 'side', nav: 'light', cards: 'border', radius: 'soft', density: 'compact', font: 'Geist', head: 'sans', paper: '#FAFAFA', surface: '#FFFFFF' },
    washi: { mode: 'light', accent: 'indigo', layout: 'double', nav: 'color', cards: 'flat', radius: 'square', density: 'comfy', font: 'Noto Sans JP', head: 'serif', paper: '#F3EEE4', surface: '#FFFDF8' },
    noche: { mode: 'dark', accent: 'azul', layout: 'double', nav: 'dark', cards: 'border', radius: 'soft', density: 'comfy', font: 'IBM Plex Sans', head: 'sans', paper: '#F5F6F8', surface: '#FFFFFF' }
  };
  const PRESET_INFO = {
    coral: ['Coral moderno', 'Propuesta actual · menú doble'], tabler: ['Azul corporativo', 'Menú superior · estilo Tabler'],
    filament: ['Laravel Filament', 'Lateral claro · ámbar'], minimal: ['Minimal monocromo', 'Compacto · estilo shadcn/ui'],
    washi: ['Washi · Ai-iro', 'Estética japonesa · índigo'], noche: ['Nocturno', 'Modo oscuro · azul']
  };
  const FONTS = ['Manrope', 'IBM Plex Sans', 'Geist', 'Source Sans 3', 'Be Vietnam Pro', 'Noto Sans JP'];
  const KEYS = ['preset', 'mode', 'accent', 'layout', 'nav', 'cards', 'radius', 'density', 'font', 'head', 'paper', 'surface'];
  const STORE = 'crm-theme-v1';
  const I = {
    users: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 3a4 4 0 1 1 0 8 4 4 0 0 1 0-8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
    mega: 'M3 11v2a1 1 0 0 0 1 1h3l5 4V6L7 10H4a1 1 0 0 0-1 1zM16 8a5 5 0 0 1 0 8M19 5a9 9 0 0 1 0 14',
    heart: 'M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z',
    globe: 'M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20zM2 12h20M12 2a15 15 0 0 1 4 10 15 15 0 0 1-4 10 15 15 0 0 1-4-10 15 15 0 0 1 4-10z',
    brief: 'M4 7h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2zM16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16',
    tablet: 'M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zM12 18h.01',
    cal: 'M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zM16 2v4M8 2v4M3 10h18',
    sliders: 'M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6',
    help: 'M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20zM9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01',
    dash: 'M4 3h5a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zM15 3h5a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zM15 12h5a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1zM4 16h5a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1z',
    userPlus: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 3a4 4 0 1 1 0 8 4 4 0 0 1 0-8zM19 8v6M22 11h-6',
    search: 'M11 4a7 7 0 1 1 0 14 7 7 0 0 1 0-14zM21 21l-4.3-4.3',
    visit: 'M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3',
    stats: 'M12 20V10M18 20V4M6 20v-4',
    layers: 'M12 2l10 5-10 5L2 7zM2 17l10 5 10-5M2 12l10 5 10-5',
    award: 'M12 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12zM15.5 13 17 22l-5-3-5 3 1.5-9',
    target: 'M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20zM12 6a6 6 0 1 1 0 12 6 6 0 0 1 0-12zM12 10a2 2 0 1 1 0 4 2 2 0 0 1 0-4z',
    shuffle: 'M16 3h5v5M4 20 21 3M21 16v5h-5M15 15l6 6M4 4l5 5',
    plusBox: 'M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zM12 8v8M8 12h8'
  };
  const MODS = [
    { key: 'clientes', label: 'Clientes', d: I.users, href: 'Clientes-Resumen.dc.html', color: '#C8343A', desc: 'Registro, búsqueda, grupos y rangos de clientes.' },
    { key: 'promo', label: 'Promociones', d: I.mega, href: '#', color: '#A21CAF', desc: 'Campañas, mensajes y cupones para tus clientes.' },
    { key: 'mipagina', label: 'Mi página', d: I.heart, href: '#', color: '#7C3AED', desc: 'Portal del miembro: tarjeta, puntos y perfil.' },
    { key: 'web', label: 'Sitio web', d: I.globe, href: '#', color: '#4F46E5', desc: 'Contenido y noticias de tu página web.' },
    { key: 'soporte', label: 'Soporte operativo', d: I.brief, href: '#', color: '#2563EB', desc: 'Herramientas para el trabajo diario del personal.' },
    { key: 'terminales', label: 'Terminales', d: I.tablet, href: '#', color: '#0E7490', desc: 'Tablets y dispositivos registrados en tienda.' },
    { key: 'reservas', label: 'Reservas', d: I.cal, href: '#', color: '#0F766E', desc: 'Agenda, citas y disponibilidad por tienda.' },
    { key: 'config', label: 'Configuración', d: I.sliders, href: '#', color: '#475569', desc: 'Tiendas, personal y datos de la empresa.' }
  ];
  const CNAV = [
    { head: true, label: 'Clientes' },
    { key: 'resumen', label: 'Resumen', d: I.dash, href: 'Clientes-Resumen.dc.html' },
    { key: 'nuevo', label: 'Nuevo cliente', d: I.userPlus, href: 'Clientes-Nuevo.dc.html' },
    { key: 'buscar', label: 'Buscar clientes', d: I.search, href: 'Clientes-Buscar.dc.html' },
    { key: 'visita', label: 'Registrar visita', d: I.visit, href: 'Clientes-Visita.dc.html' },
    { key: 'stats', label: 'Estadísticas', d: I.stats, href: 'Clientes-Estadisticas.dc.html' },
    { head: true, label: 'Configuración' },
    { key: 'campos', label: 'Campos y CSV', d: I.sliders, href: 'Clientes-Campos.dc.html' },
    { key: 'grupos', label: 'Grupos de clientes', d: I.layers, href: 'Clientes-Grupos.dc.html' },
    { key: 'motivos', label: 'Motivos de 1ª visita', d: I.target, href: 'Clientes-Motivos.dc.html' },
    { key: 'rangos', label: 'Rangos de clientes', d: I.award, href: 'Clientes-Rangos.dc.html' },
    { key: 'reglas', label: 'Asignación de rangos', d: I.shuffle, href: 'Clientes-Reglas.dc.html' },
    { key: 'info', label: 'Información adicional', d: I.plusBox, href: 'Clientes-InfoAdicional.dc.html' }
  ];

  const pick = (s) => { const o = {}; KEYS.forEach((k) => { o[k] = s[k]; }); return o; };
  const initial = (key) => ({ ...PRESETS[PRESETS[key] ? key : 'coral'], preset: PRESETS[key] ? key : 'coral' });
  const load = () => {
    try {
      const v = JSON.parse(window.localStorage.getItem(STORE) || 'null');
      return v && ACC[v.accent] && FONTS.indexOf(v.font) >= 0 ? pick(v) : null;
    } catch (e) { return null; }
  };
  const save = (s) => { try { window.localStorage.setItem(STORE, JSON.stringify(pick(s))); } catch (e) {} };

  const tokens = (s) => {
    const dark = s.mode === 'dark';
    const accent = (dark ? ACC_DARK : ACC)[s.accent] || ACC.coral;
    const base = ACC[s.accent] || ACC.coral;
    const R = { square: [4, 3], soft: [12, 8], round: [18, 12] }[s.radius] || [12, 8];
    const comfy = s.density !== 'compact';
    const P = dark
      ? { bg: '#0E1015', surface: '#161920', surface2: '#1E222B', border: '#2A2F3A', text: '#E8EAEE', muted: '#B4BBC8', faint: '#8B93A3', inputBg: '#0E1015', req: '#F06A6F' }
      : { bg: s.paper || '#F5F6F8', surface: s.surface || '#FFFFFF', surface2: '#F1F2F5', border: '#E4E7EC', text: '#14171F', muted: '#4A5162', faint: '#6B7280', inputBg: '#FFFFFF', req: '#B42318' };
    const font = "'" + s.font + "', system-ui, sans-serif";
    const head = s.head === 'serif' ? "'Noto Serif JP', serif" : font;
    const cardBorder = dark || s.cards === 'border' ? P.border : (s.cards === 'flat' ? 'transparent' : '#EEF0F3');
    const cardShadow = !dark && s.cards === 'shadow' ? '0 1px 2px rgba(16,24,40,.05), 0 6px 16px rgba(16,24,40,.06)' : 'none';
    const onAccent = dark ? '#0E1015' : '#FFFFFF';
    const ctrlH = comfy ? 40 : 34;
    const btnBase = 'display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: ' + (comfy ? 42 : 36) + 'px; padding: 0 16px; border-radius: ' + R[1] + 'px; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none; white-space: nowrap; box-sizing: border-box; ';
    const T = {
      ...P, dark, dir: s.layout === 'top' ? 'column' : 'row', font, head,
      fs: comfy ? 14 : 13, pad: comfy ? 32 : 20, gap: comfy ? 20 : 14, rowH: comfy ? 56 : 42, ctrlH, navH: comfy ? 40 : 34,
      R: R[0], r: R[1], accent, onAccent,
      accentSoft: accent + (dark ? '2E' : '1A'), accentSoftFg: dark ? accent : base, link: dark ? accent : base,
      card: 'background: ' + P.surface + '; border: 1px solid ' + cardBorder + '; box-shadow: ' + cardShadow + '; border-radius: ' + R[0] + 'px',
      input: 'box-sizing: border-box; width: 100%; height: ' + ctrlH + 'px; padding: 0 12px; border: 1px solid ' + P.border + '; border-radius: ' + R[1] + 'px; background: ' + P.inputBg + '; color: ' + P.text + '; font: inherit; min-width: 0',
      label: 'display: block; font-size: 12px; font-weight: 700; color: ' + P.muted + '; margin-bottom: 6px',
      btn: btnBase + 'border: 1px solid ' + P.border + '; background: ' + P.surface + '; color: ' + P.text,
      btnP: btnBase + 'border: 1px solid ' + accent + '; background: ' + accent + '; color: ' + onAccent,
      iconBtn: 'font-size: 18px; width: 44px; height: 44px; border-radius: ' + R[1] + 'px; border: 0; background: transparent; color: ' + P.muted + '; display: inline-flex; align-items: center; justify-content: center; cursor: pointer'
    };
    let N;
    if (s.nav === 'dark') N = { bg: dark ? '#090B0F' : '#12151C', fg: '#9AA3B2', title: '#FFFFFF', label: '#8B93A3', activeBg: '#2A303D', activeFg: '#FFFFFF', brandBg: accent, brandFg: onAccent, border: dark ? P.border : 'transparent', btnBorder: 'rgba(255,255,255,.22)' };
    else if (s.nav === 'light') N = { bg: P.surface, fg: P.muted, title: P.text, label: P.faint, activeBg: T.accentSoft, activeFg: T.accentSoftFg, brandBg: accent, brandFg: onAccent, border: P.border, btnBorder: P.border };
    else N = { bg: base, fg: 'rgba(255,255,255,.85)', title: '#FFFFFF', label: 'rgba(255,255,255,.75)', activeBg: 'rgba(255,255,255,.2)', activeFg: '#FFFFFF', brandBg: 'rgba(255,255,255,.2)', brandFg: '#FFFFFF', border: 'transparent', btnBorder: 'rgba(255,255,255,.35)' };
    N.btn = 'display: inline-flex; align-items: center; gap: 8px; height: 36px; padding: 0 12px; border-radius: ' + R[1] + 'px; border: 1px solid ' + N.btnBorder + '; background: transparent; color: ' + N.title + '; font: inherit; font-weight: 700; cursor: pointer';
    const isDouble = s.layout === 'double', isSimple = s.layout === 'side', isTop = s.layout === 'top';
    const SN = isDouble
      ? { w: 252, bg: P.surface, fg: P.muted, title: P.text, label: P.faint, activeBg: T.accentSoft, activeFg: T.accentSoftFg, border: P.border }
      : { w: 260, bg: N.bg, fg: N.fg, title: N.title, label: N.label, activeBg: N.activeBg, activeFg: N.activeFg, border: N.border };
    return { T, N, SN, isDouble, isSimple, isTop, isSide: !isTop };
  };

  // ctx: { module: 'clientes' | null, active: key, crumbs: [labels] }
  const shell = (s, tk, ctx) => {
    const { T, N, SN, isDouble, isSimple, isTop } = tk;
    const link = (it, on, X) => ({
      ...it, link: true, head: false,
      style: 'display: flex; align-items: center; gap: 10px; min-height: ' + T.navH + 'px; padding: 0 12px; border-radius: ' + T.r + 'px; text-decoration: none; background: ' + (on ? X.activeBg : 'transparent') + '; color: ' + (on ? X.activeFg : X.fg) + '; font-weight: ' + (on ? 700 : 500)
    });
    const head = (label) => ({ head: true, link: false, label, style: 'padding: 14px 12px 6px; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: ' + SN.label });
    let sideItems = [];
    if (ctx.module) {
      sideItems = CNAV.map((it) => (it.head ? head(it.label) : link(it, it.key === ctx.active, SN)));
      if (isSimple) sideItems = sideItems.concat([head('Otros módulos')], MODS.filter((m) => m.key !== ctx.module).map((m) => link(m, false, SN)));
    } else {
      sideItems = [head('Módulos')].concat(MODS.map((m) => link(m, false, SN)));
    }
    const rail = MODS.map((m) => {
      const on = m.key === ctx.module;
      return { ...m, style: 'width: 44px; height: 44px; border-radius: ' + T.r + 'px; display: flex; align-items: center; justify-content: center; text-decoration: none; background: ' + (on ? N.activeBg : 'transparent') + '; color: ' + (on ? N.activeFg : N.fg) };
    });
    const topItems = MODS.map((m) => {
      const on = m.key === ctx.module;
      return { ...m, style: 'min-height: 36px; padding: 0 10px; display: flex; align-items: center; border-radius: ' + T.r + 'px; text-decoration: none; font-size: 14px; font-weight: 600; white-space: nowrap; background: ' + (on ? N.activeBg : 'transparent') + '; color: ' + (on ? N.activeFg : N.fg) };
    });
    const tabs = ctx.module ? CNAV.filter((it) => !it.head).map((it) => {
      const on = it.key === ctx.active;
      return { ...it, style: 'min-height: 46px; padding: 0 12px; display: flex; align-items: center; white-space: nowrap; text-decoration: none; font-size: 14px; font-weight: ' + (on ? 700 : 500) + '; color: ' + (on ? T.text : T.muted) + '; box-shadow: inset 0 -2px 0 ' + (on ? T.accent : 'transparent') };
    }) : [];
    return {
      rail, sideItems, topItems, tabs, hasTabs: tabs.length > 0,
      sideOn: ctx.module ? !isTop : isSimple, sideBrand: isSimple, sideTitle: isDouble && !!ctx.module,
      crumbs: (ctx.crumbs || []).map((c, i, a) => ({ label: c.label, href: c.href || '#', last: i === a.length - 1, notLast: i < a.length - 1 }))
    };
  };

  const panel = (s, set, applyPreset) => {
    const seg = (key, opts, isFont) => opts.map(([v, label]) => {
      const on = s[key] === v;
      const bg = on ? '#14171F' : '#F5F6F8', fg = on ? '#FFFFFF' : '#4A5162', border = on ? '#14171F' : '#E4E7EC';
      const ff = isFont ? "'" + v + "', sans-serif" : 'inherit';
      return { label, on, bg, fg, border, ff, pick: () => set({ [key]: v }),
        style: 'flex: 1 1 auto; min-height: 38px; padding: 0 12px; border-radius: 8px; border: 1px solid ' + border + '; background: ' + bg + '; color: ' + fg + '; font: inherit; font-size: 13px; font-weight: 700; cursor: pointer; font-family: ' + ff };
    });
    const groups = [
      { title: 'Modo', opts: seg('mode', [['light', 'Claro'], ['dark', 'Oscuro']]) },
      { title: 'Disposición del menú', opts: seg('layout', [['double', 'Doble'], ['side', 'Lateral'], ['top', 'Superior']]) },
      { title: 'Estilo del menú', opts: seg('nav', [['dark', 'Oscuro'], ['light', 'Claro'], ['color', 'Color']]) },
      { title: 'Tarjetas', opts: seg('cards', [['border', 'Borde'], ['shadow', 'Sombra'], ['flat', 'Plano']]) },
      { title: 'Esquinas', opts: seg('radius', [['square', 'Rectas'], ['soft', 'Suaves'], ['round', 'Redondeadas']]) },
      { title: 'Densidad', opts: seg('density', [['comfy', 'Cómoda'], ['compact', 'Compacta']]) },
      { title: 'Tipografía', opts: seg('font', FONTS.map((f) => [f, f]), true) },
      { title: 'Títulos', opts: seg('head', [['sans', 'Sans'], ['serif', 'Serif (Mincho)']]) }
    ];
    const swatches = Object.keys(ACC).map((k) => ({ label: ACC_NAME[k], color: ACC[k], on: s.accent === k, ring: s.accent === k ? '#14171F' : 'transparent', pick: () => set({ accent: k }) }));
    const presets = Object.keys(PRESETS).map((k) => {
      const p = PRESETS[k];
      return {
        name: PRESET_INFO[k][0], desc: PRESET_INFO[k][1], on: s.preset === k, ring: s.preset === k ? '#14171F' : '#E4E7EC',
        c1: p.mode === 'dark' ? '#0E1015' : p.paper, c2: p.nav === 'color' ? ACC[p.accent] : (p.nav === 'dark' ? '#12151C' : '#FFFFFF'), c3: ACC[p.accent],
        pick: () => applyPreset(k)
      };
    });
    const tk = tokens(s);
    const css = [
      ':root {',
      '  --color-bg: ' + tk.T.bg + ';',
      '  --color-surface: ' + tk.T.surface + ';',
      '  --color-border: ' + tk.T.border + ';',
      '  --color-text: ' + tk.T.text + ';',
      '  --color-muted: ' + tk.T.muted + ';',
      '  --color-primary: ' + tk.T.accent + ';',
      '  --color-on-primary: ' + tk.T.onAccent + ';',
      '  --nav-bg: ' + tk.N.bg + ';',
      '  --nav-fg: ' + tk.N.fg + ';',
      '  --radius-card: ' + tk.T.R + 'px;',
      '  --radius-control: ' + tk.T.r + 'px;',
      '  --row-height: ' + tk.T.rowH + 'px;',
      "  --font-sans: '" + s.font + "', system-ui, sans-serif;",
      '  --font-heading: ' + tk.T.head + ';',
      '}',
      '/* <body data-layout="' + s.layout + '" data-nav="' + s.nav + '" data-cards="' + s.cards + '" data-mode="' + s.mode + '"> */'
    ].join('\n');
    return { groups, swatches, presets, css };
  };

  return { ACC, PRESETS, STORE, I, MODS, CNAV, pick, initial, load, save, tokens, shell, panel };
}
