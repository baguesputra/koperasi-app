# Plan: Audit Log Tab di Halaman Pengaturan

## Overview
Menambahkan tab "Audit Log" di halaman Pengaturan (`/pengaturan`) untuk menampilkan riwayat perubahan sistem dengan filter pencarian, rentang tanggal, dan pagination.

---

## Requirements

| Feature | Implementasi |
|---------|-------------|
| **Tab baru** | "Audit Log" dengan icon `Activity` (lucide-react) |
| **Date range filter** | Date picker "Dari" - "Sampai" (optional, default: 30 hari terakhir) |
| **Search** | Full-text search pada `aksi` dan `keterangan` (debounced) |
| **Pagination** | 20 per halaman, Inertia `preserveState` |
| **Expandable row** | Klik baris → tampilkan `data_lama` / `data_baru` (JSON pretty) |
| **Permission** | Reuse existing `pengaturan.kelola` |

---

## Files to Modify

### 1. `app/Http/Controllers/PengaturanController.php`

**Changes:**
- Add `'audit'` ke `TAB_DIPERBOLEHKAN`
- In `index()`: fetch audit logs when `tabAktif === 'audit'`
- Query params: `search`, `date_from`, `date_to`
- Return paginated `auditLogs` to Inertia

```php
// Di dalam index()
$auditLogs = null;
if ($tabAktif === 'audit') {
    $query = AuditLog::with('user')->latest();

    if ($request->filled('search')) {
        $search = $request->string('search');
        $query->where(function ($q) use ($search) {
            $q->where('aksi', 'like', "%{$search}%")
              ->orWhere('keterangan', 'like', "%{$search}%");
        });
    }

    if ($request->filled('date_from')) {
        $query->whereDate('created_at', '>=', $request->date('date_from'));
    }
    if ($request->filled('date_to')) {
        $query->whereDate('created_at', '<=', $request->date('date_to'));
    }

    $auditLogs = $query->paginate(20)
        ->withQueryString()
        ->through(fn ($log) => [
            'id' => $log->id,
            'aksi' => $log->aksi,
            'keterangan' => $log->keterangan,
            'data_lama' => $log->data_lama,
            'data_baru' => $log->data_baru,
            'user' => $log->user ? [
                'name' => $log->user->name,
                'no_karyawan' => $log->user->no_karyawan,
            ] : null,
            'created_at' => $log->created_at->format('d M Y H:i'),
        ]);
}
```

---

### 2. `resources/js/Pages/Pengaturan/Partials/TabAuditLog.jsx` (NEW)

**Component Structure:**
```jsx
export default function TabAuditLog({ auditLogs, filter }) {
    const [search, setSearch] = useState(filter.search ?? '');
    const [dateFrom, setDateFrom] = useState(filter.date_from ?? '');
    const [dateTo, setDateTo] = useState(filter.date_to ?? '');
    const [expandedId, setExpandedId] = useState(null);
    const [debouncedSearch, setDebouncedSearch] = useState(search);

    // Debounce search (300ms)
    useEffect(() => {
        const timer = setTimeout(() => setDebouncedSearch(search), 300);
        return () => clearTimeout(timer);
    }, [search]);

    function handleFilterChange() {
        router.get(route('pengaturan.index'), {
            tab: 'audit',
            search: debouncedSearch || undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
        }, { preserveState: true, replace: true });
    }

    // UI: Search input, Date pickers, Table, Pagination
    // Expandable row untuk JSON pretty-print data_lama/data_baru
}
```

**Features:**
- Search input (debounced 300ms) → filter `aksi` + `keterangan`
- Two date pickers: `date_from`, `date_to` (native `<input type="date">`)
- Table columns: Aksi, Keterangan, User (name + no_karyawan), Waktu
- Click row → expand/collapse → pretty-print JSON (`data_lama` / `data_baru`)
- Pagination: `auditLogs.links` + `router.get` with `preserveState`
- Reset filter button

---

### 3. `resources/js/Pages/Pengaturan/Index.jsx`

**Changes:**
- Add import: `import TabAuditLog from './Partials/TabAuditLog';`
- Add to `tabs` array:
  ```js
  { key: 'audit', label: 'Audit Log', icon: Activity },
  ```
- Conditional render:
  ```jsx
  {tabAktif === 'audit' && <TabAuditLog auditLogs={auditLogs} filter={filterAudit} />}
  ```
- Extract filter props from page props: `filterAudit` = `{ search, date_from, date_to }`

---

## Route
No changes needed. Existing route:
```
GET /pengaturan  →  PengaturanController@index
```
Filter params passed via query string: `?tab=audit&search=...&date_from=...&date_to=...`

---

## Permission
- Route sudah dilindungi: `permission:pengaturan.kelola` + `password.confirm`
- Tidak perlu permission baru

---

## UI/UX Details

### Filter Bar (top of table)
```
[ Search input (debounced) ]  [ Date From ]  [ Date To ]  [ Reset ]
```

### Table Columns
| Aksi | Keterangan | User | Waktu | (expand) |
|------|------------|------|-------|----------|
| `pinjaman_setujui_ketua` | "Pinjaman #123 disetujui..." | Budi (BEN-000001) | 24 Aug 2026 14:30 | ▼ |

### Expandable Row
```
data_lama: { "status": "approved_bendahara" }
data_baru: { "status": "aktif", "catatan_ketua": "Disetujui", ... }
```
- Render dengan `<pre>` + syntax highlight (optional) atau `JSON.stringify(obj, null, 2)`

### Pagination
- Gunakan `auditLogs.links` dari Laravel paginator
- `router.get(url, { preserveState: true })`

---

## Dependencies
- `lucide-react`: `Activity` icon (sudah ada)
- `date-fns` atau native `Date` untuk formatting (sudah dipakai di project)

---

## Testing Checklist
- [ ] Tab muncul di header
- [ ] Default load: 30 hari terakhir, no search
- [ ] Search filter works (debounced)
- [ ] Date range filter works
- [ ] Combined search + date works
- [ ] Pagination preserves filters
- [ ] Expand row shows JSON correctly
- [ ] Reset filter clears all
- [ ] Empty state handling

---

## Estimated Effort
- Backend: ~30 menit
- Frontend (TabAuditLog): ~60 menit
- Integration: ~15 menit
- **Total: ~1.75 jam**

---

## Next Steps
Approve plan → implement backend → implement frontend → test → done.