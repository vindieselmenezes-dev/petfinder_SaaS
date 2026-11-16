/* Baralho de videos educativos. Dados em assets/data/videos.json */
(function () {
  var deck = document.getElementById('epDeck');
  if (!deck) return;
  var CW = 140, MAXEDGE = 26, act = 0, vids = [];
  var cnt = document.getElementById('epCount');

  function ytId(v) {
    var m = String(v || '').match(/(?:v=|youtu\.be\/|embed\/|shorts\/)([\w-]{11})/);
    return m ? m[1] : (/^[\w-]{11}$/.test(v) ? v : '');
  }
  function norm(v) {
    var o = { titulo: v.titulo || 'Video', duracao: v.duracao || '', tipo: v.tipo, capa: v.capa || '' };
    if (v.tipo === 'youtube') {
      o.id = ytId(v.youtube);
      if (!o.id) return null;
      if (!o.capa) o.capa = 'https://img.youtube.com/vi/' + o.id + '/hqdefault.jpg';
    } else if (v.tipo === 'arquivo' && v.arquivo) {
      o.arquivo = v.arquivo;
    } else return null;
    return o;
  }
  function el(tag, cls, txt) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (txt) e.textContent = txt;
    return e;
  }
  function build(list, soon) {
    deck.innerHTML = '';
    list.forEach(function (v, i) {
      var b = el('button', 'ep-card' + (soon ? ' ep-soon' : ''));
      b.type = 'button';
      b.setAttribute('aria-label', v.titulo);
      var edge = el('span', 'ep-edge');
      edge.appendChild(el('span', '', String(i + 1)));
      var body = el('span', 'ep-body');
      var th = el('span', 'ep-thumb');
      if (v.capa) th.style.backgroundImage = 'url("' + v.capa + '")';
      var pl = el('span', 'ep-play');
      pl.textContent = '\u25B6';
      th.appendChild(pl);
      if (v.duracao) th.appendChild(el('em', '', v.duracao));
      body.appendChild(th);
      body.appendChild(el('p', 'ep-titulo', v.titulo));
      body.appendChild(el('span', 'ep-ver', soon ? 'Em breve' : '\u25B6 Assistir agora'));
      b.appendChild(edge);
      b.appendChild(body);
      b.addEventListener('click', function () {
        if (soon) return;
        if (i === act) play(v); else { act = i; pos(); }
      });
      deck.appendChild(b);
    });
    pos();
  }
  function pos() {
    var cs = deck.children, n = cs.length;
    if (!n) return;
    var W = deck.clientWidth || 300;
    var edge = n > 1 ? Math.min(MAXEDGE, Math.max(14, (W - CW) / (n - 1))) : 0;
    for (var i = 0; i < n; i++) {
      var l = i <= act ? i * edge : act * edge + CW + (i - act - 1) * edge;
      cs[i].style.left = l + 'px';
      cs[i].style.zIndex = i === act ? 50 : i;
    }
    if (cnt) cnt.textContent = (act + 1) + ' / ' + n;
  }
  function play(v) {
    var m = document.getElementById('epModal');
    if (!m || !window.bootstrap) return;
    var box = document.getElementById('epPlayer');
    document.getElementById('epModalTitle').textContent = v.titulo;
    box.innerHTML = '';
    if (v.tipo === 'youtube') {
      var f = document.createElement('iframe');
      f.src = 'https://www.youtube-nocookie.com/embed/' + v.id + '?autoplay=1&rel=0';
      f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
      f.allowFullscreen = true;
      f.title = v.titulo;
      box.appendChild(f);
    } else {
      var vd = document.createElement('video');
      vd.controls = true; vd.autoplay = true; vd.playsInline = true;
      vd.src = v.arquivo;
      if (v.capa) vd.poster = v.capa;
      box.appendChild(vd);
    }
    window.bootstrap.Modal.getOrCreateInstance(m).show();
  }
  var modal = document.getElementById('epModal');
  if (modal) modal.addEventListener('hidden.bs.modal', function () {
    document.getElementById('epPlayer').innerHTML = '';
  });
  window.addEventListener('resize', pos);

  var soonList = [1, 2, 3].map(function () { return { titulo: 'Novos vídeos em breve', duracao: '', capa: '' }; });
  fetch('assets/data/videos.json', { cache: 'no-cache' })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      vids = (d.videos || []).filter(function (v) { return v.ativo !== false; }).map(norm).filter(Boolean);
      if (vids.length) build(vids, false); else build(soonList, true);
    })
    .catch(function () { build(soonList, true); });
})();
