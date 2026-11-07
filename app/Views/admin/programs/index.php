<?php
// Admin Programs Management View
$catMap = [];
foreach ($categories as $c) {
    $catMap[(int)$c['id']] = $c['name'];
}
$catCounts = [];
foreach ($programs as $p) {
    if (!empty($p['category_id'])) {
        $cid = (int)$p['category_id'];
        $catCounts[$cid] = ($catCounts[$cid] ?? 0) + 1;
    }
}
$catGroupLabels = ['online' => 'Kelas Online', 'offline' => 'Kelas Offline', 'non_kelas' => 'Non Kelas'];
?>
<div x-data="{ addModal: false, editModal: false, editItem: {}, catModal: false, catEditId: 0, catName: '', catGroup: '' }" class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Kelola Program & Harga</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Atur materi silabus bertingkat, status badge baru (NEW), rasio bahasa inggris, dan update harga kelas.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="catModal = true; catEditId = 0; catName = ''" class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs sm:text-sm px-5 py-2.5 rounded-2xl shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                <span>Kelola Kategori</span>
            </button>
            <button @click="addModal = true" class="bg-pepsi-blue hover:bg-blue-900 text-white font-bold text-xs sm:text-sm px-5 py-2.5 rounded-2xl shadow-md flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Program Baru</span>
            </button>
        </div>
    </div>

    <!-- Programs Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-5 py-4">Kode & Nama Program</th>
                        <th class="px-5 py-4">Kategori</th>
                        <th class="px-5 py-4">Level</th>
                        <th class="px-5 py-4">Fokus</th>
                        <th class="px-5 py-4">Harga / Biaya</th>
                        <th class="px-5 py-4">Rasio English</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($programs as $p): 
                        $syllabusList = json_decode($p['syllabus'], true) ?: [];
                        $syllabusText = implode("\n", $syllabusList);
                    ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900 text-sm"><?= sanitize($p['title']) ?></span>
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-mono text-[10px]"><?= sanitize($p['code']) ?></span>
                                <?php if ($p['is_new']): ?>
                                <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-600 font-bold text-[9px] uppercase">NEW</span>
                                <?php endif; ?>
                            </div>
                            <span class="text-[11px] text-slate-500 line-clamp-1 mt-0.5"><?= sanitize($p['description']) ?></span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <?php if (!empty($p['category_id']) && isset($catMap[(int)$p['category_id']])): ?>
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-violet-50 text-violet-700">
                                <?= sanitize($catMap[(int)$p['category_id']]) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-300">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-pepsi-blue">
                                <?= sanitize($p['level_label']) ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= $p['orientation'] === 'ielts' ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-700' ?>">
                                <?= strtoupper($p['orientation']) ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap font-heading font-black text-sm text-pepsi-navy">
                            <?= formatRupiah($p['price']) ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="font-bold text-slate-700"><?= sanitize($p['english_ratio']) ?></span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $p['is_active'] ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-400' ?>">
                                <?= $p['is_active'] ? 'Aktif' : 'Non-Aktif' ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right whitespace-nowrap space-x-1">
                            <button @click="editItem = <?= htmlspecialchars(json_encode([
                                'id' => $p['id'],
                                'code' => $p['code'],
                                'title' => $p['title'],
                                'category_id' => $p['category_id'] !== null ? (int)$p['category_id'] : '',
                                'level_category' => $p['level_category'],
                                'level_label' => $p['level_label'],
                                'orientation' => $p['orientation'],
                                'price' => $p['price'],
                                'english_ratio' => $p['english_ratio'],
                                'description' => $p['description'],
                                'syllabus' => $syllabusText,
                                'final_exam' => $p['final_exam'],
                                'is_new' => (bool)$p['is_new'],
                                'is_active' => (bool)$p['is_active']
                            ])) ?>; editModal = true;" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                                Edit
                            </button>
                            <form action="<?= url('admin/programs/delete/' . $p['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Hapus program ini?')">
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-pepsi-red font-bold transition">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL TAMBAH PROGRAM -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="addModal = false" class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Tambah Program Kursus Baru</h3>
                <button @click="addModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form action="<?= url('admin/programs/store') ?>" method="POST" class="space-y-4 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Program *</label>
                        <input type="text" name="code" required placeholder="Contoh: LT1, SFI-AC" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Judul Program *</label>
                        <input type="text" name="title" required placeholder="Contoh: Let's Talk 1" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kategori Program</label>
                    <select name="category_id" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold text-violet-700">
                        <option value="">-- Tanpa Kategori --</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= sanitize($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kategori Level *</label>
                        <select name="level_category" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                            <option value="basic">basic</option>
                            <option value="pre-intermediate">pre-intermediate</option>
                            <option value="intermediate">intermediate</option>
                            <option value="pre-advanced">pre-advanced</option>
                            <option value="advanced">advanced</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Label Tampilan Level</label>
                        <input type="text" name="level_label" required placeholder="Basic Level, Pre-Advanced" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Fokus / Orientasi</label>
                        <select name="orientation" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                            <option value="speaking">speaking</option>
                            <option value="ielts">ielts</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Harga Kursus (Rp) *</label>
                        <input type="number" name="price" required placeholder="150000" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Target Rasio English</label>
                        <input type="text" name="english_ratio" placeholder="Contoh: 50%, 80%" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Deskripsi Singkat Program</label>
                    <textarea name="description" rows="2" placeholder="Jelaskan tujuan dan output program..." class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50"></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Materi Silabus (1 materi per baris)</label>
                    <textarea name="syllabus" rows="4" placeholder="Topik 1: Basic Introduction&#10;Topik 2: Small Talk Mastery&#10;Topik 3: Expressing Preferences" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-mono"></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Ujian Akhir Kelulusan</label>
                    <input type="text" name="final_exam" placeholder="Contoh: Face to Face Topics - Question Card" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_new" value="1" id="is_new_add" class="rounded">
                    <label for="is_new_add" class="font-bold text-slate-700 cursor-pointer">Beri Badge "NEW!"</label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Simpan Program</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT PROGRAM -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editModal = false" class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-heading font-black text-xl text-pepsi-navy">Edit Program Kursus</h3>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form :action="'<?= url('admin/programs/update/') ?>' + editItem.id" method="POST" class="space-y-4 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Program *</label>
                        <input type="text" name="code" x-model="editItem.code" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Judul Program *</label>
                        <input type="text" name="title" x-model="editItem.title" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kategori Program</label>
                    <select name="category_id" x-model="editItem.category_id" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold text-violet-700">
                        <option value="">-- Tanpa Kategori --</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= sanitize($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kategori Level *</label>
                        <select name="level_category" x-model="editItem.level_category" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                            <option value="basic">basic</option>
                            <option value="pre-intermediate">pre-intermediate</option>
                            <option value="intermediate">intermediate</option>
                            <option value="pre-advanced">pre-advanced</option>
                            <option value="advanced">advanced</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Label Tampilan Level</label>
                        <input type="text" name="level_label" x-model="editItem.level_label" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Fokus / Orientasi</label>
                        <select name="orientation" x-model="editItem.orientation" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                            <option value="speaking">speaking</option>
                            <option value="ielts">ielts</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Harga Kursus (Rp) *</label>
                        <input type="number" name="price" x-model="editItem.price" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Target Rasio English</label>
                        <input type="text" name="english_ratio" x-model="editItem.english_ratio" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Deskripsi Singkat Program</label>
                    <textarea name="description" x-model="editItem.description" rows="2" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50"></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Materi Silabus (1 materi per baris)</label>
                    <textarea name="syllabus" x-model="editItem.syllabus" rows="4" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-mono"></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Ujian Akhir Kelulusan</label>
                    <input type="text" name="final_exam" x-model="editItem.final_exam" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50">
                </div>

                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 font-bold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_new" value="1" :checked="editItem.is_new" class="rounded">
                        <span>Badge "NEW!"</span>
                    </label>
                    <label class="flex items-center gap-2 font-bold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" :checked="editItem.is_active" class="rounded">
                        <span>Status Aktif</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pepsi-blue text-white font-bold">Perbarui Program</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL KELOLA KATEGORI -->
    <div x-show="catModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="catEditId = 0" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-heading font-black text-xl text-pepsi-navy">Kelola Kategori Program</h3>
                    <p class="text-xs text-slate-400">Tambah, edit, atau hapus kategori program kursus.</p>
                </div>
                <button @click="catModal = false; catEditId = 0" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <form action="<?= url('admin/programs/store-category') ?>" method="POST" class="flex flex-wrap items-end gap-2 text-xs">
                <div class="flex-1 min-w-[160px]">
                    <label class="block font-bold text-slate-700 mb-1">Kategori Baru *</label>
                    <input type="text" name="name" required maxlength="50" placeholder="Contoh: PEPSI KIDS" class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Grup Laporan</label>
                    <select name="report_group" class="rounded-xl border border-slate-200 p-2.5 bg-slate-50 font-bold text-slate-700">
                        <option value="">- Tidak dikelompokkan -</option>
                        <option value="offline">Kelas Offline</option>
                        <option value="online">Kelas Online</option>
                        <option value="non_kelas">Non Kelas</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-pepsi-blue text-white font-bold whitespace-nowrap">Tambah</button>
            </form>

            <div class="border-t border-slate-100 pt-3">
                <?php if (empty($categories)): ?>
                <p class="text-xs text-slate-400 text-center py-4">Belum ada kategori.</p>
                <?php else: ?>
                <ul class="divide-y divide-slate-100 text-xs max-h-72 overflow-y-auto">
                    <?php foreach ($categories as $cat): $used = $catCounts[(int)$cat['id']] ?? 0; ?>
                    <li class="py-2.5 flex items-center gap-2">
                        <template x-if="catEditId !== <?= (int)$cat['id'] ?>">
                            <div class="flex-1 flex items-center justify-between gap-2">
                                <div>
                                    <span class="font-bold text-slate-800"><?= sanitize($cat['name']) ?></span>
                                    <?php $rg = $cat['report_group'] ?? ''; if ($rg !== '' && isset($catGroupLabels[$rg])): ?>
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-violet-50 text-violet-700 ml-2"><?= sanitize($catGroupLabels[$rg]) ?></span>
                                    <?php else: ?>
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-400 ml-2">Tanpa Grup</span>
                                    <?php endif; ?>
                                    <span class="text-[10px] text-slate-400 ml-2"><?= $used ?> program</span>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button @click="catEditId = <?= (int)$cat['id'] ?>; catName = <?= htmlspecialchars(json_encode($cat['name']), ENT_QUOTES) ?>; catGroup = <?= htmlspecialchars(json_encode($cat['report_group'] ?? ''), ENT_QUOTES) ?>"
                                            class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">Edit</button>
                                    <form action="<?= url('admin/programs/delete-category/' . (int)$cat['id']) ?>" method="POST"
                                          onsubmit="return confirm('Hapus kategori &quot;<?= sanitize($cat['name']) ?>&quot;?<?php if ($used > 0): ?> Kategori ini dipakai <?= $used ?> program.<?php endif; ?>')">
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 font-bold transition">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </template>
                        <template x-if="catEditId === <?= (int)$cat['id'] ?>">
                            <form action="<?= url('admin/programs/update-category/' . (int)$cat['id']) ?>" method="POST" class="flex-1 flex items-center gap-2">
                                <input type="text" name="name" maxlength="50" x-model="catName" required
                                       class="flex-1 rounded-xl border border-slate-200 p-2 bg-slate-50 font-bold">
                                <select name="report_group" x-model="catGroup" class="rounded-xl border border-slate-200 p-2 bg-slate-50 font-bold text-slate-700">
                                    <option value="">Tanpa Grup</option>
                                    <option value="offline">Kelas Offline</option>
                                    <option value="online">Kelas Online</option>
                                    <option value="non_kelas">Non Kelas</option>
                                </select>
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-pepsi-blue text-white font-bold">Simpan</button>
                                <button type="button" @click="catEditId = 0" class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                            </form>
                        </template>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-end">
                <button @click="catModal = false; catEditId = 0" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs">Tutup</button>
            </div>
        </div>
    </div>

</div>
