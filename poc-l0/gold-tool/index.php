<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
require_token();

$lang  = (string) ($_GET['lang'] ?? '');
$token = (string) ($_GET['t'] ?? '');
$qs    = $token !== '' ? '&t=' . rawurlencode($token) : '';

if (!isset(LANGS[$lang])) {
    // Page de choix de langue : un lien par locuteur.
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Gold set — choix de la langue</title>';
    echo '<style>body{font:16px/1.6 system-ui,sans-serif;max-width:38rem;margin:4rem auto;padding:0 1rem}';
    echo 'a{display:block;padding:1rem;margin:.6rem 0;border:1px solid #ccc;border-radius:.5rem;text-decoration:none;color:#111}';
    echo 'a:hover{background:#f4f4f4}</style>';
    echo '<h1>Transcription du gold set</h1><p>Choisissez votre langue :</p>';
    foreach (LANGS as $code => $label) {
        $n = count(clips_for($code));
        echo '<a href="?lang=' . $code . $qs . '"><strong>' . htmlspecialchars($label) . '</strong><br>'
           . $n . ' clips</a>';
    }
    exit;
}

// Caractères absents du clavier, par langue.
$CHARS = [
    'fon' => ['ɖ', 'ɛ', 'ɔ', 'ŋ', 'ɣ', 'ʋ'],
    'yor' => ['ẹ', 'ọ', 'ṣ', 'gb'],
];
// Marques tonales combinantes : s'appliquent à la lettre qui précède.
$TONES = [
    ["\u{0301}", 'ton haut  ́'],
    ["\u{0300}", 'ton bas  ̀'],
    ["\u{0304}", 'ton moyen  ̄'],
    ["\u{030C}", 'ton montant  ̌'],
    ["\u{0302}", 'ton descendant  ̂'],
    ["\u{0303}", 'nasal  ̃'],
];
$label = LANGS[$lang];
?>
<!doctype html>
<html lang="fr">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gold set — <?= htmlspecialchars($label) ?></title>
<style>
  :root { --bd:#d4d4d8; --mut:#71717a; --acc:#2563eb; }
  * { box-sizing:border-box }
  body { font:16px/1.6 system-ui,-apple-system,sans-serif; max-width:46rem; margin:1.5rem auto; padding:0 1rem; color:#18181b }
  h1 { font-size:1.25rem; margin:0 0 .25rem }
  .mut { color:var(--mut); font-size:.9rem }
  .bar { height:.5rem; background:#e4e4e7; border-radius:99px; overflow:hidden; margin:.75rem 0 1.25rem }
  .bar > i { display:block; height:100%; background:var(--acc); width:0; transition:width .2s }
  audio { width:100%; margin:.5rem 0 }
  textarea { width:100%; min-height:6.5rem; font-size:1.15rem; padding:.6rem; border:1px solid var(--bd); border-radius:.4rem; font-family:inherit }
  textarea:focus { outline:2px solid var(--acc); border-color:transparent }
  .keys { display:flex; flex-wrap:wrap; gap:.35rem; margin:.5rem 0 }
  .keys button { font-size:1.1rem; min-width:2.4rem; padding:.35rem .5rem; border:1px solid var(--bd); background:#fff; border-radius:.35rem; cursor:pointer }
  .keys button:hover { background:#f4f4f5 }
  .keys .tone { font-size:.85rem; min-width:auto; color:var(--mut) }
  nav { display:flex; gap:.5rem; margin-top:1rem; flex-wrap:wrap }
  nav button { padding:.6rem 1rem; border-radius:.4rem; border:1px solid var(--bd); background:#fff; cursor:pointer; font-size:1rem }
  nav .primary { background:var(--acc); color:#fff; border-color:var(--acc) }
  nav .skip { color:#b91c1c }
  #status { margin-left:auto; align-self:center; font-size:.85rem; color:var(--mut) }
  .done { color:#15803d } .todo { color:var(--mut) }
  .hint { background:#fafafa; border:1px solid var(--bd); border-radius:.4rem; padding:.6rem .8rem; font-size:.85rem; margin-top:1.25rem }
</style>

<h1>Gold set — <?= htmlspecialchars($label) ?></h1>
<div class="mut">Clip <span id="pos">–</span> / <span id="total">–</span> · <span id="counts"></span></div>
<div class="bar"><i id="prog"></i></div>

<audio id="player" controls preload="none"></audio>

<textarea id="ref" spellcheck="false" placeholder="Tapez ici ce qui est réellement dit, mot à mot, avec les tons…"></textarea>

<div class="keys" id="letters"></div>
<div class="keys" id="tones"></div>

<nav>
  <button id="prev">← Précédent</button>
  <button id="next" class="primary">Enregistrer et suivant →</button>
  <button id="skip" class="skip">Pas de parole (musique / inaudible)</button>
  <span id="status"></span>
</nav>

<div class="hint">
  <strong>Règles :</strong> transcrivez <em>verbatim</em> (ce qui est dit, pas ce qui aurait dû être dit),
  en conservant les <strong>marques tonales</strong>. Un segment code-switché se transcrit dans la graphie de sa langue.
  Ignorez musique et bruits : utilisez « Pas de parole ». Tout est sauvegardé automatiquement.
</div>

<script>
const LANG = <?= json_encode($lang) ?>;
const QS = <?= json_encode($qs) ?>;
const LETTERS = <?= json_encode($CHARS[$lang] ?? [], JSON_UNESCAPED_UNICODE) ?>;
const TONES = <?= json_encode($TONES, JSON_UNESCAPED_UNICODE) ?>;

const $ = (id) => document.getElementById(id);
let clips = [], i = 0, dirty = false;

function insert(text) {
  const t = $('ref');
  const s = t.selectionStart, e = t.selectionEnd;
  t.value = t.value.slice(0, s) + text + t.value.slice(e);
  t.selectionStart = t.selectionEnd = s + text.length;
  t.focus();
  dirty = true;
}

function buildKeys() {
  LETTERS.forEach(c => {
    const b = document.createElement('button');
    b.textContent = c; b.type = 'button';
    b.onclick = () => insert(c);
    $('letters').append(b);
  });
  TONES.forEach(([mark, name]) => {
    const b = document.createElement('button');
    b.className = 'tone'; b.type = 'button';
    b.textContent = name; b.title = 'Applique le ton à la lettre précédente';
    b.onclick = () => insert(mark);
    $('tones').append(b);
  });
}

function render() {
  const c = clips[i];
  $('pos').textContent = i + 1;
  $('player').src = `api.php?a=audio&id=${encodeURIComponent(c.id)}${QS}`;
  $('ref').value = c.reference || '';
  dirty = false;
  refreshCounts();
}

function refreshCounts() {
  const done = clips.filter(c => c.status === 'done').length;
  const skipped = clips.filter(c => c.status === 'skipped').length;
  const total = clips.length;
  $('counts').innerHTML = `<span class="done">${done} transcrits</span> · ${skipped} sans parole · `
    + `<span class="todo">${total - done - skipped} restants</span>`;
  $('prog').style.width = ((done + skipped) / total * 100) + '%';
}

async function save(status) {
  const c = clips[i];
  const reference = status === 'skipped' ? '' : $('ref').value.trim();
  // "done" seulement si du texte a été saisi ; sinon on laisse à faire.
  const st = status === 'skipped' ? 'skipped' : (reference ? 'done' : 'todo');
  c.reference = reference; c.status = st;
  $('status').textContent = 'Enregistrement…';
  try {
    const r = await fetch(`api.php?a=save${QS.replace('&', '&')}`, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: c.id, reference, status: st }),
    });
    if (!r.ok) throw new Error(await r.text());
    $('status').textContent = 'Enregistré ✓';
    dirty = false;
  } catch (e) {
    $('status').textContent = '⚠ Échec de sauvegarde';
    alert('La sauvegarde a échoué : ' + e.message);
    throw e;
  }
  refreshCounts();
}

async function go(delta, status) {
  await save(status ?? 'done');
  i = Math.min(clips.length - 1, Math.max(0, i + delta));
  render();
}

$('next').onclick = () => go(1);
$('prev').onclick = () => go(-1);
$('skip').onclick = () => go(1, 'skipped');
$('ref').oninput = () => { dirty = true; $('status').textContent = ''; };
window.onbeforeunload = () => dirty ? 'Une saisie non enregistrée sera perdue.' : undefined;

(async () => {
  buildKeys();
  const r = await fetch(`api.php?a=clips&lang=${LANG}${QS}`);
  const d = await r.json();
  clips = d.clips;
  if (!clips.length) { document.body.innerHTML = '<p>Aucun clip pour cette langue.</p>'; return; }
  $('total').textContent = clips.length;
  // Reprendre au premier clip non traité
  const firstTodo = clips.findIndex(c => c.status === 'todo');
  i = firstTodo >= 0 ? firstTodo : 0;
  render();
})();
</script>
</html>
