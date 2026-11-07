<?php
// Admin Session Management View - Release Sesi Login
?>
<div x-data="{ releaseAllModal: false }" class="space-y-6">

    <!-- Top Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-50 border border-cyan-200 text-cyan-800 text-xs font-bold mb-2">
                <svg class="w-3.5 h-3.5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                </svg>
                <span>Kontrol Sesi &amp; Keamanan Akun</span>
            </div>
            <h1 class="font-heading font-black text-2xl sm:text-3xl text-pepsi-navy">Release Sesi Login</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Bebaskan sesi akun yang terkunci akibat browser tertutup tanpa logout, berganti perangkat, atau cookie terhapus.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button @click="releaseAllModal = true" class="bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs sm:text-sm px-4 py-2.5 rounded-2xl shadow-md flex items-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Release Semua Sesi Pengguna</span>
            </button>
        </div>
    </div>

    <!-- Info Banner Panduan -->
    <div class="bg-gradient-to-r from-cyan-900 to-slate-900 text-white rounded-3xl p-5 sm:p-6 shadow-xl border border-cyan-800/40 relative overflow-hidden">
        <div class="absolute -right-8 -bottom-8 w-40 h-40 bg-cyan-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-cyan-500/20 text-cyan-300 flex items-center justify-center shrink-0 border border-cyan-400/30">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="space-y-1.5 text-xs sm:text-sm">
                <h3 class="font-black text-base text-cyan-200">Bagaimana Cara Kerja Release Sesi?</h3>
                <p class="text-slate-300 leading-relaxed">
                    Sistem MR.PEPSI memberlakukan aturan <b class="text-white">1 Akun = 1 Perangkat</b> untuk mencegah penyalahgunaan. Apabila staf atau kasir <b class="text-amber-300">menutup jendela browser langsung tanpa klik "Logout"</b>, sesi mereka akan tetap tersimpan selama 30 menit dan menolak login dari perangkat lain.
                </p>
                <p class="text-slate-400 text-xs">
                    💡 Klik tombol <span class="bg-red-500/30 text-red-200 font-bold px-1.5 py-0.5 rounded border border-red-400/30">Release Sesi</span> pada akun bersangkutan untuk membuka kunci akun secara instan tanpa perlu menunggu waktu timeout.
                </p>
            </div>
        </div>
    </div>

    <!-- Summary Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-pepsi-blue flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Akun</p>
                <p class="text-xl sm:text-2xl font-black text-slate-900"><?= $totalCount ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.828a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Online Aktif</p>
                <p class="text-xl sm:text-2xl font-black text-emerald-600"><?= $activeCount ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Sesi Menggantung (Idle)</p>
                <p class="text-xl sm:text-2xl font-black text-amber-600"><?= $idleCount ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Offline / Bebas</p>
                <p class="text-xl sm:text-2xl font-black text-slate-600"><?= $offlineCount ?></p>
            </div>
        </div>
    </div>

    <!-- Tabel Sesi User -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-heading font-black text-base text-slate-900">Daftar Status Sesi Akun</h2>
                <p class="text-xs text-slate-400">Pantau dan reset sesi yang terkunci secara instan</p>
            </div>
            <a href="<?= url('admin/sessions') ?>" class="text-xs font-bold text-pepsi-blue hover:text-blue-900 flex items-center gap-1 self-start sm:self-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Perbarui Data (Refresh)</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium">
                <thead class="bg-pepsi-navy text-white text-[11px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-5 py-4">Pengguna / Akun</th>
                        <th class="px-5 py-4">Username</th>
                        <th class="px-5 py-4">Peran (Role)</th>
                        <th class="px-5 py-4">Status Sesi</th>
                        <th class="px-5 py-4">Aktivitas Terakhir</th>
                        <th class="px-5 py-4 text-right">Aksi Release</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($users as $u): 
                        $isSelf = ((int)$u['id'] === $currentUserId);
                        $hasSession = !empty($u['has_session']);
                        $isOnline = !empty($u['is_online']);
                    ?>
                    <tr class="hover:bg-slate-50/80 transition">
                        <!-- User Name & Avatar -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl <?= $u['role'] === 'owner' ? 'bg-violet-600' : ($u['role'] === 'admin' ? 'bg-pepsi-blue' : 'bg-emerald-600') ?> text-white flex items-center justify-center font-black text-xs shadow-sm">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 text-sm block"><?= sanitize($u['name']) ?></span>
                                    <?php if ($isSelf): ?>
                                        <span class="inline-block text-[10px] font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full mt-0.5">Sesi Anda Saat Ini</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>

                        <!-- Username -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="font-mono text-slate-700 font-bold bg-slate-100 px-2.5 py-1 rounded-lg"><?= sanitize($u['username']) ?></span>
                        </td>

                        <!-- Role -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <?php if ($u['role'] === 'owner'): ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-violet-100 text-violet-700">Owner</span>
                            <?php elseif ($u['role'] === 'admin'): ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-blue-100 text-pepsi-blue">Administrator</span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-700">Kasir Pepsi Food &amp; Drink</span>
                            <?php endif; ?>
                        </td>

                        <!-- Status Sesi -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <?php if ($isOnline): ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Online (Sedang Aktif)</span>
                                </span>
                            <?php elseif ($hasSession): ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200" title="Akun ini belum logout saat browser ditutup">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span>Sesi Menggantung (Terkunci)</span>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500">
                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                    <span>Bebas (Offline)</span>
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Last Seen -->
                        <td class="px-5 py-4 whitespace-nowrap text-slate-500">
                            <?php if (!empty($u['last_seen'])): ?>
                                <div class="font-medium text-slate-700">
                                    <?= tanggalIndo($u['last_seen'], 'd M Y') ?>, <?= date('H:i', strtotime($u['last_seen'])) ?> WIB
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    <?php 
                                        $diffMinutes = round((time() - strtotime($u['last_seen'])) / 60);
                                        if ($diffMinutes <= 1) echo 'Baru saja';
                                        elseif ($diffMinutes < 60) echo "{$diffMinutes} menit yang lalu";
                                        else echo round($diffMinutes / 60) . ' jam yang lalu';
                                    ?>
                                </div>
                            <?php else: ?>
                                <span class="text-slate-400 italic">Belum ada aktivitas</span>
                            <?php endif; ?>
                        </td>

                        <!-- Action Button -->
                        <td class="px-5 py-4 text-right whitespace-nowrap">
                            <?php if ($hasSession || $isOnline): ?>
                                <form action="<?= url('admin/sessions/release/' . $u['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Lepaskan sesi untuk user <?= addslashes($u['name']) ?>? User akan langsung bisa login dari perangkat mana pun.')">
                                    <button type="submit" class="px-3.5 py-1.5 rounded-xl font-bold text-xs transition inline-flex items-center gap-1.5 <?= $isSelf ? 'bg-amber-100 hover:bg-amber-200 text-amber-800' : 'bg-red-50 hover:bg-red-100 text-pepsi-red border border-red-200' ?>">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                        </svg>
                                        <span><?= $isSelf ? 'Reset Sesi Saya (Logout)' : 'Release Sesi' ?></span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="px-3 py-1 rounded-xl bg-slate-100 text-slate-400 text-xs font-semibold">
                                    Sesi Kosong
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL KONFIRMASI RELEASE SEMUA SESI -->
    <div x-show="releaseAllModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="releaseAllModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex items-center gap-3 text-amber-600 pb-3 border-b border-slate-100">
                <div class="w-10 h-10 rounded-2xl bg-amber-50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-heading font-black text-lg text-slate-900">Release Semua Sesi?</h3>
                    <p class="text-xs text-slate-500">Keluarkan seluruh akun pengguna yang sedang menggantung</p>
                </div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                Tindakan ini akan mengosongkan status login semua akun user staf dan kasir yang masih menggantung. Mereka akan dapat login kembali tanpa halangan.
            </p>

            <form action="<?= url('admin/sessions/release-all') ?>" method="POST" class="space-y-4 pt-2">
                <label class="flex items-start gap-2.5 cursor-pointer bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <input type="checkbox" name="include_self" value="1" class="mt-0.5 rounded border-slate-300 text-pepsi-red focus:ring-pepsi-red">
                    <span class="text-xs text-slate-700 font-semibold leading-snug">
                        Keluarkan juga akun saya sendiri saat ini (Anda akan dialihkan ke halaman login)
                    </span>
                </label>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="releaseAllModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-pepsi-red hover:bg-red-800 text-white font-black text-xs shadow-md transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Ya, Release Semua Sesi</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
