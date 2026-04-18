# Anna Albums Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the fixed `category` field on Anna's art with user-defined albums, and add a tab filter bar on the public gallery.

**Architecture:** Two files only: `anna/api.php` (PHP backend, JSON file store) and `anna/index.html` (vanilla-JS SPA). Migration runs once inside `readData()` when `albums` is missing. Backend goes first so the frontend has real responses to develop against.

**Tech Stack:** PHP 7+, vanilla JS, no build tools, no test framework. Verification is via a local PHP server (`php -S localhost:8000 -t anna`) in the browser, then live deploy via FTP.

**Spec:** `docs/superpowers/specs/2026-04-18-anna-albums-design.md`

**Safety for existing art:** Task 9 explicitly excludes `data.json` from the FTP upload — the live server migrates its own file on the next request, and all upload files under `anna/uploads/` are untouched. The `deleteAlbum` action only nulls `albumId` on art items; it never touches image files.

**Conventions followed from existing code:**
- IDs: `bin2hex(random_bytes(8))` in PHP.
- JSON writes: `json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)`.
- API success: `{ ok: true }`; errors: HTTP 4xx + `{ error: "msg" }`.
- All admin actions call `requireAuth()` first.
- Frontend render pattern: mutate state → `await loadData(); render(); adminTab('<tab>')`.
- HTML escaping: always wrap user data in `esc(str)` when building template strings.

---

## Task 0: Start local PHP server

**Files:** none.

- [ ] **Step 1:** From repo root, run:

```bash
php -S localhost:8000 -t anna
```

Leave it running in a separate terminal for all subsequent tasks. Open `http://localhost:8000/` — the existing gallery should load.

(If PHP is not installed, skip local verification and rely on code review + single end-of-plan live deploy. All tasks still work.)

---

## Task 1: Data migration in `readData()`

**Files:**
- Modify: `anna/api.php:40-43`
- Modify: `anna/api.php:13-19` (initial seed)

- [ ] **Step 1:** Replace the current `readData()` body with a version that migrates old data on first read:

```php
function readData() {
    global $dataFile;
    $data = json_decode(file_get_contents($dataFile), true);

    if (!isset($data['albums'])) {
        $data['albums'] = [
            [
                'id'    => bin2hex(random_bytes(8)),
                'name'  => 'Tegneskole',
                'order' => 0,
            ],
        ];
        foreach ($data['art'] as &$a) {
            unset($a['category']);
            $a['albumId'] = null;
        }
        unset($a);
        writeData($data);
    }

    return $data;
}
```

- [ ] **Step 2:** Update the initial-seed block so fresh installs start with an empty `albums` array. Inside the `if (!file_exists($dataFile))` block, change the array literal to:

```php
[
    'about'    => ['text' => '', 'image' => ''],
    'albums'   => [],
    'art'      => [],
    'settings' => ['layout' => 'masonry', 'theme' => 'glad']
]
```

- [ ] **Step 3: Verify**

Write a pre-migration test file and hit `getData`:

```bash
cat > anna/data.json <<'EOF'
{
  "about": {"text": "", "image": ""},
  "art": [{"id":"a","title":"t","description":"","category":"tegning","image":"","visible":true,"featured":false,"order":0,"createdAt":"2026-01-01"}],
  "settings": {"layout":"masonry","theme":"glad"}
}
EOF
curl -s 'http://localhost:8000/api.php?action=getData' | python -m json.tool
```

Expected: response contains `"albums": [{..., "name":"Tegneskole", "order":0}]` and the art item has `"albumId": null` with no `"category"` key. Open `anna/data.json` — it should now be rewritten with the new shape.

- [ ] **Step 4: Commit**

```bash
git add anna/api.php anna/data.json
git commit -m "anna: migrate data.json to albums model (drop category, seed Tegneskole)"
```

---

## Task 2: Album CRUD actions in `api.php`

**Files:**
- Modify: `anna/api.php` — add four new `case` blocks inside `switch ($action)` directly after `case 'saveSettings':` (before `default:` at line 255).

- [ ] **Step 1:** Insert the four action cases:

```php
    case 'createAlbum':
        requireAuth();
        $body = json_decode(file_get_contents('php://input'), true);
        $name = trim($body['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 60) {
            http_response_code(400);
            echo json_encode(['error' => 'Navn skal være 1–60 tegn']);
            exit;
        }
        $data = readData();
        $maxOrder = -1;
        foreach ($data['albums'] as $al) $maxOrder = max($maxOrder, $al['order'] ?? 0);
        $id = bin2hex(random_bytes(8));
        $data['albums'][] = ['id' => $id, 'name' => $name, 'order' => $maxOrder + 1];
        writeData($data);
        echo json_encode(['ok' => true, 'id' => $id]);
        break;

    case 'updateAlbum':
        requireAuth();
        $body = json_decode(file_get_contents('php://input'), true);
        $id   = $body['id'] ?? '';
        $name = trim($body['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 60) {
            http_response_code(400);
            echo json_encode(['error' => 'Navn skal være 1–60 tegn']);
            exit;
        }
        $data = readData();
        foreach ($data['albums'] as &$al) {
            if ($al['id'] === $id) { $al['name'] = $name; break; }
        }
        unset($al);
        writeData($data);
        echo json_encode(['ok' => true]);
        break;

    case 'deleteAlbum':
        requireAuth();
        $body = json_decode(file_get_contents('php://input'), true);
        $id   = $body['id'] ?? '';
        $data = readData();
        $data['albums'] = array_values(array_filter(
            $data['albums'],
            fn($al) => $al['id'] !== $id
        ));
        foreach ($data['art'] as &$a) {
            if (($a['albumId'] ?? null) === $id) $a['albumId'] = null;
        }
        unset($a);
        writeData($data);
        echo json_encode(['ok' => true]);
        break;

    case 'reorderAlbums':
        requireAuth();
        $body  = json_decode(file_get_contents('php://input'), true);
        $order = $body['order'] ?? [];
        $data  = readData();
        $byId = [];
        foreach ($data['albums'] as $al) $byId[$al['id']] = $al;
        $sorted = [];
        foreach ($order as $i => $id) {
            if (isset($byId[$id])) {
                $byId[$id]['order'] = $i;
                $sorted[] = $byId[$id];
                unset($byId[$id]);
            }
        }
        foreach ($byId as $al) $sorted[] = $al;
        $data['albums'] = $sorted;
        writeData($data);
        echo json_encode(['ok' => true]);
        break;
```

- [ ] **Step 2: Verify** — open `http://localhost:8000`, log in (password `Hartmann`). In browser DevTools console:

```js
await fetch('api.php?action=createAlbum', {method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({name:'TEST'})}).then(r=>r.json())
```

Expected: `{ok: true, id: "<hex>"}`. Then `getData` should list both Tegneskole and TEST. Clean up:

```js
const id = (await fetch('api.php?action=getData').then(r=>r.json())).albums.find(a=>a.name==='TEST').id
await fetch('api.php?action=deleteAlbum', {method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({id})}).then(r=>r.json())
```

- [ ] **Step 3: Commit**

```bash
git add anna/api.php
git commit -m "anna: add createAlbum / updateAlbum / deleteAlbum / reorderAlbums actions"
```

---

## Task 3: Swap `category` → `albumId` in `upload` and `updateArt`

**Files:**
- Modify: `anna/api.php:138` (the `'category'` line inside the `upload` new-item array)
- Modify: `anna/api.php:167` (the whitelist loop in `updateArt`)

- [ ] **Step 1:** In the `upload` case, find `'category' => $_POST['category'] ?? 'andet',` and replace with:

```php
            'albumId'     => (isset($_POST['albumId']) && $_POST['albumId'] !== '') ? $_POST['albumId'] : null,
```

- [ ] **Step 2:** In `updateArt`, replace the three-line block that starts with `foreach (['title','description','category'] as $k) {` (lines 167-169) with:

```php
                foreach (['title','description'] as $k) {
                    if (isset($body[$k])) $item[$k] = $body[$k];
                }
                if (array_key_exists('albumId', $body)) {
                    $item['albumId'] = ($body['albumId'] === '' || $body['albumId'] === null) ? null : $body['albumId'];
                }
```

Leave the `visible` and `featured` handling (the `if (isset($body['visible']))…` block just below) unchanged.

- [ ] **Step 3: Verify** — in DevTools console (still logged in):

```js
const d = await fetch('api.php?action=getData').then(r=>r.json())
const albumId = d.albums[0].id
const artId   = d.art[0].id
await fetch('api.php?action=updateArt', {method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({id: artId, albumId})}).then(r=>r.json())
;(await fetch('api.php?action=getData').then(r=>r.json())).art.find(a=>a.id===artId).albumId
```

Expected: final line returns the `albumId` you assigned. Clear it:

```js
await fetch('api.php?action=updateArt', {method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({id: artId, albumId: null})}).then(r=>r.json())
```

- [ ] **Step 4: Commit**

```bash
git add anna/api.php
git commit -m "anna: replace category with albumId in upload and updateArt"
```

---

## Task 4: Frontend state and default data shape

**Files:**
- Modify: `anna/index.html:444-451` (the `S` object literal)

- [ ] **Step 1:** Replace the `S` definition with:

```js
const S = {
  view: 'gallery',
  admin: false,
  adminTab: null,
  editingArtId: null,
  editingAlbumId: null,
  albumFilter: null,
  data: { about: { text: '', image: '' }, albums: [], art: [], settings: { layout: 'masonry', theme: 'glad' } },
  lbIndex: -1,
};
```

- [ ] **Step 2: Verify** — hard-reload `http://localhost:8000`. In console, type `S` — confirm `albumFilter` and `data.albums` are present. Gallery still renders (existing art visible).

- [ ] **Step 3: Commit**

```bash
git add anna/index.html
git commit -m "anna: add albumFilter and albums to frontend state"
```

---

## Task 5: Public gallery tab bar + filter

**Files:**
- Modify: `anna/index.html:165` (CSS — insert new rules)
- Modify: `anna/index.html:531-557` (`renderGallery`)
- Modify: `anna/index.html:559-568` (`galleryItem`)
- Modify: `anna/index.html:589-592` (`getVisibleArt`)

- [ ] **Step 1:** Insert this CSS block immediately above the existing `.gallery-item` rules at line ~170:

```css
.album-tabs{
  display:flex;flex-wrap:wrap;gap:8px;
  justify-content:center;margin:0 auto 32px;max-width:960px;padding:0 20px;
}
.album-tab{
  padding:8px 16px;border-radius:999px;
  background:transparent;border:1px solid var(--card-border);
  color:var(--text2);font-size:.9rem;cursor:pointer;
  transition:background var(--transition),color var(--transition),border-color var(--transition);
}
.album-tab:hover{color:var(--text);background:var(--bg2)}
.album-tab.active{color:var(--btn-text);background:var(--accent);border-color:var(--accent)}
```

- [ ] **Step 2:** Replace `renderGallery()` (lines 531-557) with:

```js
function renderGallery() {
  const art = S.admin ? S.data.art : S.data.art.filter(a => a.visible);
  const filtered = S.albumFilter
    ? art.filter(a => a.albumId === S.albumFilter)
    : art;
  const sorted = [...filtered].sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
  const layout = S.data.settings.layout;
  const albums = [...(S.data.albums || [])].sort((a,b) => (a.order ?? 0) - (b.order ?? 0));

  let html = '<div class="hero slide-up"><h1 class="site-title">Anna</h1>';
  html += '<p class="subtitle">Tegninger, malerier &amp; mere</p></div>';

  if (albums.length) {
    html += '<div class="album-tabs fade-in">';
    html += `<button class="album-tab${S.albumFilter === null ? ' active' : ''}" onclick="filterAlbum(null)">Alle</button>`;
    for (const al of albums) {
      html += `<button class="album-tab${S.albumFilter === al.id ? ' active' : ''}" onclick="filterAlbum('${al.id}')">${esc(al.name)}</button>`;
    }
    html += '</div>';
  }

  if (!sorted.length) {
    html += '<div class="gallery-empty"><div class="empty-icon">' + (THEMES[S.data.settings.theme]?.emoji || '') + '</div>';
    html += '<p>Endnu ingen kunstv\u00E6rker at vise</p></div>';
    return html;
  }

  if (layout === 'featured') {
    const feat = sorted.find(a => a.featured) || sorted[0];
    const rest = sorted.filter(a => a.id !== feat.id);
    html += '<div class="gallery-featured fade-in">';
    html += '<div class="feat">' + galleryItem(feat) + '</div>';
    html += '<div class="rest">' + rest.map(a => galleryItem(a)).join('') + '</div>';
    html += '</div>';
  } else {
    const cls = layout === 'grid' ? 'gallery-grid' : 'gallery-masonry';
    html += `<div class="${cls} fade-in">` + sorted.map(a => galleryItem(a)).join('') + '</div>';
  }
  return html;
}

function filterAlbum(id) {
  S.albumFilter = id;
  render();
}
```

- [ ] **Step 3:** Replace `galleryItem(a)` (lines 559-568) — drops the category line:

```js
function galleryItem(a) {
  const hiddenCls = (!a.visible && S.admin) ? ' hidden-item' : '';
  return `<div class="gallery-item${hiddenCls}" onclick="openLightbox('${a.id}')">
    <img src="${esc(a.image)}" alt="${esc(a.title)}" loading="lazy">
    <div class="item-info">
      <div class="item-title">${esc(a.title)}</div>
    </div>
  </div>`;
}
```

- [ ] **Step 4:** Replace `getVisibleArt()` (lines 589-592) so lightbox navigation respects the filter:

```js
function getVisibleArt() {
  const art = S.admin ? S.data.art : S.data.art.filter(a => a.visible);
  const filtered = S.albumFilter
    ? art.filter(a => a.albumId === S.albumFilter)
    : art;
  return [...filtered].sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
}
```

- [ ] **Step 5: Verify** — hard-reload `http://localhost:8000`:
  - Two pill tabs above the grid: **Alle · Tegneskole**.
  - Click Tegneskole → grid empties (nothing assigned yet). Click Alle → everything back.
  - Gallery cards no longer show category text under the title.
  - Open lightbox, arrow-navigate — only cycles currently-visible items.

- [ ] **Step 6: Commit**

```bash
git add anna/index.html
git commit -m "anna: album tab filter on public gallery, remove category label from cards"
```

---

## Task 6: Upload form — album dropdown

**Files:**
- Modify: `anna/index.html:720-728` (the Kategori form-group)
- Modify: `anna/index.html:747` (`doUpload` FormData)

- [ ] **Step 1:** In `renderUploadPanel()`, replace the Kategori `form-group` (lines 720-728) with:

```js
        <div class="form-group">
          <label>Album</label>
          <select id="upload-album">
            <option value="">(intet album)</option>
            ${[...(S.data.albums || [])].sort((a,b)=>(a.order??0)-(b.order??0))
              .map(al => `<option value="${al.id}">${esc(al.name)}</option>`).join('')}
          </select>
        </div>
```

- [ ] **Step 2:** In `doUpload`, replace the `fd.append('category', …)` line with:

```js
  fd.append('albumId', document.getElementById('upload-album').value);
```

- [ ] **Step 3: Verify** — reload, log in, open Upload panel. Dropdown shows `(intet album)` + `Tegneskole`. Upload a small test image with Tegneskole selected. After the success toast, click the Tegneskole tab on the public gallery — new item appears. Delete it via the Galleri panel afterwards.

- [ ] **Step 4: Commit**

```bash
git add anna/index.html
git commit -m "anna: album dropdown on upload form"
```

---

## Task 7: Edit-art form + admin card meta

**Files:**
- Modify: `anna/index.html:772` (the meta line in `renderManagePanel`)
- Modify: `anna/index.html:791-795` (the Kategori form-group in edit-form)
- Modify: `anna/index.html:848-849` (`saveArtEdit` payload)

- [ ] **Step 1:** Replace the meta line (line 772) with an album-aware version:

```js
        <div class="meta">${a.albumId ? esc((S.data.albums.find(al => al.id === a.albumId) || {name:'?'}).name) : '<span style="color:var(--text2);font-style:italic">Intet album</span>'} \u00B7 ${a.createdAt}${a.featured ? ' \u2B50' : ''}${!a.visible ? ' \u00B7 skjult' : ''}</div>
```

- [ ] **Step 2:** Replace the Kategori form-group (lines 791-795) with an Album dropdown:

```js
        <div class="form-group"><label>Album</label>
          <select id="edit-art-album">
            <option value=""${!a.albumId ? ' selected' : ''}>(intet album)</option>
            ${[...(S.data.albums || [])].sort((al1,al2)=>(al1.order??0)-(al2.order??0))
              .map(al => `<option value="${al.id}"${al.id === a.albumId ? ' selected' : ''}>${esc(al.name)}</option>`).join('')}
          </select>
        </div>
```

- [ ] **Step 3:** In `saveArtEdit`, replace the two lines that read `category` and pass it to the API with:

```js
  const albumId = document.getElementById('edit-art-album').value;
  await api('updateArt', { json: true, body: JSON.stringify({ id, title, description, albumId }) });
```

- [ ] **Step 4: Verify** — reload, log in, open Galleri. Every existing artwork shows *Intet album* in the meta. Click Rediger on one item, change Album to Tegneskole, Gem. The meta line should now read "Tegneskole". Visit public gallery → item appears under the Tegneskole tab. Set back to `(intet album)` to leave data clean.

- [ ] **Step 5: Commit**

```bash
git add anna/index.html
git commit -m "anna: album field on edit form, album name in admin gallery meta"
```

---

## Task 8: Albums admin panel

**Files:**
- Modify: `anna/index.html:427-434` (admin-bar buttons)
- Modify: `anna/index.html:694-697` (dispatcher in `adminTab`)
- Modify: `anna/index.html` — new `renderAlbumsPanel()` plus five handlers, inserted right after `deleteArt` (around line 860, before the `/* ── About edit ── */` comment).

- [ ] **Step 1:** In the admin-bar div (lines 427-434), add a new Albums tab button between Galleri and Om mig:

```html
    <button class="admin-tab" onclick="adminTab('albums')">Albums</button>
```

Final button order: Upload · Galleri · Albums · Om mig · Indstillinger · Log ud.

- [ ] **Step 2:** The dispatcher at lines 694-697 currently has one branch per known tab (`upload`, `manage`, `about-edit`, `settings`). Add a new branch for `albums` that delegates to `renderAlbumsPanel()` — follow the exact same assignment pattern as the surrounding lines (the existing `manage` branch is the closest template). Place it between the `manage` and `about-edit` branches.

- [ ] **Step 3:** Insert the new renderer and handlers right after `deleteArt` (around line 860):

```js
/* ── Albums ── */
function renderAlbumsPanel() {
  const sorted = [...(S.data.albums || [])].sort((a,b)=>(a.order??0)-(b.order??0));
  const counts = {};
  for (const a of S.data.art) {
    if (a.albumId) counts[a.albumId] = (counts[a.albumId] || 0) + 1;
  }
  let html = '<div class="admin-content"><h3>Albums</h3>';
  html += `<form onsubmit="createAlbum(event)" style="display:flex;gap:8px;margin-bottom:20px">
    <input type="text" id="new-album-name" placeholder="Nyt album..." maxlength="60" style="flex:1">
    <button type="submit" class="btn btn-primary">+ Opret</button>
  </form>`;
  if (!sorted.length) {
    html += '<p style="color:var(--text2);font-style:italic">Ingen albums endnu</p>';
  }
  sorted.forEach((al, i) => {
    const n = counts[al.id] || 0;
    const isEditing = S.editingAlbumId === al.id;
    html += `<div class="art-list-item">
      <div class="art-list-info">
        ${isEditing
          ? `<input type="text" id="edit-album-name" value="${esc(al.name)}" maxlength="60" style="width:100%" onkeydown="if(event.key==='Enter')saveAlbumEdit('${al.id}');if(event.key==='Escape')cancelAlbumEdit()">`
          : `<div class="name">${esc(al.name)}</div><div class="meta">${n} v\u00E6rk${n===1?'':'er'}</div>`}
      </div>
      <div class="art-list-actions">
        ${i > 0 ? `<button class="btn btn-ghost btn-sm" onclick="moveAlbum(${i},-1)">\u2191</button>` : ''}
        ${i < sorted.length - 1 ? `<button class="btn btn-ghost btn-sm" onclick="moveAlbum(${i},1)">\u2193</button>` : ''}
        ${isEditing
          ? `<button class="btn btn-primary btn-sm" onclick="saveAlbumEdit('${al.id}')">Gem</button>
             <button class="btn btn-ghost btn-sm" onclick="cancelAlbumEdit()">Annuller</button>`
          : `<button class="btn btn-ghost btn-sm" onclick="editAlbum('${al.id}')">Omd\u00F8b</button>
             <button class="btn btn-danger btn-sm" onclick="deleteAlbum('${al.id}', ${n})">Slet</button>`}
      </div>
    </div>`;
  });
  html += '</div>';
  return html;
}

async function createAlbum(e) {
  e.preventDefault();
  const name = document.getElementById('new-album-name').value.trim();
  if (!name) return;
  try {
    await api('createAlbum', { json: true, body: JSON.stringify({ name }) });
    await loadData(); adminTab('albums');
    toast('Album oprettet');
  } catch (err) { toast('Fejl: ' + err.message); }
}

function editAlbum(id) { S.editingAlbumId = id; adminTab('albums'); }
function cancelAlbumEdit() { S.editingAlbumId = null; adminTab('albums'); }

async function saveAlbumEdit(id) {
  const name = document.getElementById('edit-album-name').value.trim();
  if (!name) { toast('Navn kan ikke være tomt'); return; }
  try {
    await api('updateAlbum', { json: true, body: JSON.stringify({ id, name }) });
    S.editingAlbumId = null;
    await loadData(); render(); adminTab('albums');
    toast('Gemt');
  } catch (err) { toast('Fejl: ' + err.message); }
}

async function deleteAlbum(id, count) {
  const msg = count > 0
    ? `Slet dette album? ${count} v\u00E6rk${count===1?'':'er'} mister deres album-tilknytning (billederne slettes ikke).`
    : 'Slet dette album?';
  if (!confirm(msg)) return;
  if (S.albumFilter === id) S.albumFilter = null;
  await api('deleteAlbum', { json: true, body: JSON.stringify({ id }) });
  await loadData(); render(); adminTab('albums');
  toast('Album slettet');
}

async function moveAlbum(fromIdx, dir) {
  const sorted = [...S.data.albums].sort((a,b)=>(a.order??0)-(b.order??0));
  const toIdx = fromIdx + dir;
  if (toIdx < 0 || toIdx >= sorted.length) return;
  [sorted[fromIdx], sorted[toIdx]] = [sorted[toIdx], sorted[fromIdx]];
  const order = sorted.map(al => al.id);
  await api('reorderAlbums', { json: true, body: JSON.stringify({ order }) });
  await loadData(); render(); adminTab('albums');
}
```

- [ ] **Step 4: Verify (full integration)** — hard-reload, log in. Walk the flow:
  - Open Albums tab → see "Tegneskole" with "0 værker" + Omdøb / Slet buttons.
  - Create "Keramik med mor", "Pokémons" — both appear, input clears.
  - Use ↑/↓ to reorder — order persists after reload.
  - Omdøb → inline input, Enter or Gem saves, Escape/Annuller reverts.
  - In Galleri, edit an artwork, assign it to Keramik. Back to Albums → count shows "1 værk".
  - Slet Keramik → confirm dialog lists the count. OK. Public gallery tab bar no longer shows Keramik; the artwork remains under Alle with *Intet album*.
  - Public gallery: tabs reflect current album order. Clicking any tab filters correctly. Alle is always present.

- [ ] **Step 5: Commit**

```bash
git add anna/index.html
git commit -m "anna: Albums admin panel — create, rename, reorder, delete with count warning"
```

---

## Task 9: Deploy and verify on production

**Files:** none locally (FTP deploy).

- [ ] **Step 1:** Via FileZilla, upload ONLY these two files to `public_html/anna/` on simonhartmann.dk:
  - `anna/index.html`
  - `anna/api.php`

  **DO NOT upload `anna/data.json`** — the live server has the real art data and must not be overwritten. The live server's `readData()` migrates its own `data.json` on the next request.

  **DO NOT touch `anna/uploads/`** — this folder holds the actual image files.

- [ ] **Step 2:** Open `https://simonhartmann.dk/anna/` in a private window. Expected: existing artworks all present, with a new "Alle · Tegneskole" tab bar above the grid. Gallery cards no longer show category text.

- [ ] **Step 3:** Log in (password Hartmann). Open Galleri — every existing artwork shows *Intet album* in the meta. Open Albums — "Tegneskole" shows with "0 værker". Create a test album "zzz-test", confirm it appears, then delete it to validate end-to-end write path.

- [ ] **Step 4: Push local commits to origin**

```bash
git push origin main
```

---

## Self-review

- **Spec coverage:** migration (Task 1), API CRUD (Tasks 2, 3), data model change (Tasks 1 + 3), visitor tab UI (Task 5), upload form (Task 6), edit form + admin card meta (Task 7), Albums admin panel (Task 8), deploy (Task 9). All spec sections covered.
- **Existing image safety:** Task 9 Step 1 excludes `data.json` and `uploads/` from the deploy. `deleteAlbum` only sets `albumId = null` on art items; no image files are ever touched.
- **Album order:** consistently sorted by `order` ascending everywhere — tabs, dropdowns, admin panel, `getVisibleArt`.
- **Filter + lightbox:** `getVisibleArt` respects `S.albumFilter`, so lightbox arrow-navigation matches the filtered grid.
- **Delete-album UX:** `deleteAlbum` handler clears `S.albumFilter` when the deleted album was the active filter — prevents a confusing empty grid after deletion.
- **State additions:** `editingAlbumId` added in Task 4 (state) and used in Task 8 (UI).
