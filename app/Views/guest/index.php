<?php
// Guest Index View
?>
<!-- Alpine Component for interactive program filtering, syllabus modal, cost calculator, and WhatsApp registration -->
<div x-data="guestApp()" class="relative">

    <!-- HERO SECTION -->
    <section class="relative overflow-hidden pt-12 pb-20 lg:py-24 bg-gradient-to-b from-blue-50/70 via-white to-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <!-- Left Column -->
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    <div class="inline-flex flex-wrap items-center justify-center lg:justify-start gap-2.5 px-3.5 py-1.5 rounded-full bg-white border border-slate-200 shadow-sm text-xs sm:text-sm font-semibold text-slate-700">
                        <span class="w-2.5 h-2.5 rounded-full bg-pepsi-red animate-ping"></span>
                        <span>
                            Periode Berjalan Setiap Tanggal <b class="text-pepsi-blue">10</b> &amp; <b class="text-pepsi-red">25</b> Tiap Bulan
                            <?php if (!empty($nextPeriod['start_date'])): ?>
                            <span class="text-slate-400 font-normal">&middot;</span>
                            Mulai Berikutnya <b class="text-pepsi-blue"><?= sanitize(tanggalIndo($nextPeriod['start_date'], 'd F Y')) ?></b>
                            <?php if ($nextDays !== null): ?>
                            <span class="text-slate-500 font-normal">(<?= $nextDays <= 0 ? 'hari ini' : $nextDays . ' hari lagi' ?>)</span>
                            <?php endif; ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <h1 class="font-heading font-black text-4xl sm:text-5xl lg:text-6xl text-pepsi-navy tracking-tight leading-[1.1]">
                        Bicara Bahasa Inggris <span class="text-transparent bg-clip-text bg-gradient-to-r from-pepsi-blue via-indigo-600 to-pepsi-red">Lancar & Berani</span> di Pare!
                    </h1>

                    <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto lg:mx-0 leading-relaxed font-normal">
                        Kuasai speaking aktif dan persiapan IELTS intensif dengan rasio bahasa Inggris terukur, simulasi ujian nyata, dan bimbingan coach berpengalaman di Kampung Inggris Pare.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
                        <a href="#kalkulator" class="w-full sm:w-auto bg-pepsi-blue hover:bg-blue-900 text-white font-bold px-8 py-3.5 rounded-2xl shadow-xl shadow-blue-700/20 transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                            <span>Kalkulator & Pilih Program</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                        <a href="#jadwal" class="w-full sm:w-auto bg-white hover:bg-slate-50 text-slate-700 font-bold px-7 py-3.5 rounded-2xl border border-slate-200 shadow-sm transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-pepsi-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Cek Jadwal & Ruangan</span>
                        </a>
                    </div>

                    <!-- Trust Stats Mini -->
                    <div class="pt-6 border-t border-slate-200/80 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs sm:text-sm text-slate-500 font-medium">
                        <div class="flex items-center gap-2">
                            <span class="font-heading font-black text-lg text-pepsi-navy"><?= count($programs) ?>+</span>
                            <span>Program Silabus</span>
                        </div>
                        <div class="w-px h-5 bg-slate-200 hidden sm:block"></div>
                        <div class="flex items-center gap-2">
                            <span class="font-heading font-black text-lg text-pepsi-red"><?= count($tutors) ?></span>
                            <span>Coach Spesialis</span>
                        </div>
                        <div class="w-px h-5 bg-slate-200 hidden sm:block"></div>
                        <div class="flex items-center gap-2">
                            <span class="font-heading font-black text-lg text-pepsi-blue"><?= count($rooms) ?></span>
                            <span>Ruang Kelas AC</span>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Visual Showcase -->
                <div class="lg:col-span-5 relative">
                    <div class="relative mx-auto max-w-sm lg:max-w-none">
                        <!-- Gradient card backdrop -->
                        <div class="absolute -inset-2 bg-gradient-to-tr from-pepsi-blue to-pepsi-red rounded-3xl blur-xl opacity-30 transform -rotate-1"></div>
                        
                        <div class="relative bg-white rounded-3xl p-6 shadow-2xl border border-slate-100 space-y-5">
                            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-pepsi-red">MR.PEPSI SYSTEM</span>
                                    <h3 class="font-heading font-bold text-lg text-slate-900">Jadwal Kelas Terintegrasi</h3>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Online & Offline</span>
                            </div>

                            <!-- Highlights -->
                            <div class="space-y-3 text-sm">
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-pepsi-blue font-bold flex items-center justify-center">07.00</div>
                                        <div>
                                            <p class="font-bold text-slate-900">Let's Talk 1 (LT1)</p>
                                            <p class="text-xs text-slate-500">Coach Aprul &bull; Ruang Alpha</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-pepsi-blue">Rp 150rb</span>
                                </div>

                                <div class="p-3.5 rounded-2xl bg-red-50/60 border border-red-100 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-red-100 text-pepsi-red font-bold flex items-center justify-center">13.00</div>
                                        <div>
                                            <p class="font-bold text-slate-900">Vocabulary for IELTS</p>
                                            <p class="text-xs text-slate-500">Coach Pepsi &bull; Ruang Bravo</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-pepsi-red">Rp 170rb</span>
                                </div>

                                <div class="p-3.5 rounded-2xl bg-indigo-50/60 border border-indigo-100 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center">16.00</div>
                                        <div>
                                            <p class="font-bold text-slate-900">Speaking for IELTS Academics</p>
                                            <p class="text-xs text-slate-500">Coach Pepsi &bull; Ruang Romeo</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-indigo-700">Rp 180rb</span>
                                </div>
                            </div>

                            <div class="pt-2">
                                <a href="#program" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center justify-center gap-2 transition">
                                    <span>Lihat Semua 15 Program & Silabus</span> &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- SECTION 1: KATALOG PROGRAM & SILABUS -->
    <section id="program" class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <span class="text-xs font-extrabold uppercase tracking-widest text-pepsi-red">KURIKULUM & MATERI LENGKAP</span>
                <h2 class="font-heading font-black text-3xl sm:text-4xl text-pepsi-navy mt-1">Katalog Program & Silabus</h2>
                <p class="text-slate-600 text-sm sm:text-base mt-2">
                    Setiap program dirancang bertingkat dengan target rasio Bahasa Inggris, latihan intensif, dan ujian akhir kelulusan yang terstruktur.
                </p>

                <!-- Filters -->
                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <!-- Orientation Filter -->
                    <div class="inline-flex p-1 bg-slate-100 rounded-2xl border border-slate-200">
                        <button @click="orient = 'all'" :class="orient === 'all' ? 'bg-pepsi-navy text-white shadow-sm' : 'text-slate-700 hover:text-black'" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition">
                            Semua Fokus
                        </button>
                        <button @click="orient = 'speaking'" :class="orient === 'speaking' ? 'bg-pepsi-navy text-white shadow-sm' : 'text-slate-700 hover:text-black'" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition">
                            Speaking Active
                        </button>
                        <button @click="orient = 'ielts'" :class="orient === 'ielts' ? 'bg-pepsi-navy text-white shadow-sm' : 'text-slate-700 hover:text-black'" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition">
                            IELTS Preparation
                        </button>
                    </div>

                    <!-- Level Category Filter -->
                    <div class="inline-flex p-1 bg-slate-100 rounded-2xl border border-slate-200">
                        <button @click="level = 'all'" :class="level === 'all' ? 'bg-pepsi-blue text-white shadow-sm' : 'text-slate-700 hover:text-black'" class="px-3 py-2 rounded-xl text-xs sm:text-sm font-bold transition">
                            Semua Level
                        </button>
                        <button @click="level = 'basic'" :class="level === 'basic' ? 'bg-pepsi-blue text-white shadow-sm' : 'text-slate-700 hover:text-black'" class="px-3 py-2 rounded-xl text-xs sm:text-sm font-bold transition">
                            Basic
                        </button>
                        <button @click="level = 'pre-intermediate'" :class="level === 'pre-intermediate' ? 'bg-pepsi-blue text-white shadow-sm' : 'text-slate-700 hover:text-black'" class="px-3 py-2 rounded-xl text-xs sm:text-sm font-bold transition">
                            Pre-Inter
                        </button>
                        <button @click="level = 'intermediate'" :class="level === 'intermediate' ? 'bg-pepsi-blue text-white shadow-sm' : 'text-slate-700 hover:text-black'" class="px-3 py-2 rounded-xl text-xs sm:text-sm font-bold transition">
                            Intermediate
                        </button>
                        <button @click="level = 'pre-advanced'" :class="level === 'pre-advanced' ? 'bg-pepsi-blue text-white shadow-sm' : 'text-slate-700 hover:text-black'" class="px-3 py-2 rounded-xl text-xs sm:text-sm font-bold transition">
                            Pre-Adv
                        </button>
                        <button @click="level = 'advanced'" :class="level === 'advanced' ? 'bg-pepsi-blue text-white shadow-sm' : 'text-slate-700 hover:text-black'" class="px-3 py-2 rounded-xl text-xs sm:text-sm font-bold transition">
                            Advanced
                        </button>
                    </div>
                </div>
            </div>

            <!-- Program Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($programs as $prog): 
                    $syllabusList = json_decode($prog['syllabus'], true) ?: [];
                    $isIelts = $prog['orientation'] === 'ielts';
                ?>
                <div x-show="(orient === 'all' || orient === '<?= $prog['orientation'] ?>') && (level === 'all' || level === '<?= $prog['level_category'] ?>')"
                     class="bg-white rounded-3xl border border-slate-200 p-6 flex flex-col justify-between hover:shadow-xl hover:border-blue-200 transition-all transform hover:-translate-y-1 relative group">
                    
                    <?php if ($prog['is_new']): ?>
                    <span class="absolute top-4 right-4 bg-pepsi-red text-white text-[10px] font-black tracking-widest uppercase px-2.5 py-0.5 rounded-full shadow-md">
                        NEW!
                    </span>
                    <?php endif; ?>

                    <div>
                        <!-- Header badge -->
                        <div class="flex items-center gap-2 mb-3">
                            <span class="px-3 py-1 rounded-full text-xs font-bold <?= $isIelts ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-pepsi-blue' ?>">
                                <?= sanitize($prog['level_label']) ?>
                            </span>
                            <?php if (!empty($prog['english_ratio'])): ?>
                            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">
                                <?= sanitize($prog['english_ratio']) ?> English
                            </span>
                            <?php endif; ?>
                        </div>

                        <h3 class="font-heading font-black text-xl text-pepsi-navy group-hover:text-pepsi-blue transition">
                            <?= sanitize($prog['title']) ?> <span class="text-xs font-bold text-slate-400 font-sans">(<?= sanitize($prog['code']) ?>)</span>
                        </h3>
                        <p class="text-slate-600 text-sm mt-2 leading-relaxed">
                            <?= sanitize($prog['description']) ?>
                        </p>

                        <!-- Exam info -->
                        <div class="mt-4 p-3 bg-slate-50 rounded-2xl border border-slate-100 text-xs">
                            <span class="font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Ujian Akhir</span>
                            <span class="font-semibold text-slate-800"><?= sanitize($prog['final_exam']) ?></span>
                        </div>
                    </div>

                    <!-- Foot -->
                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-medium text-slate-400 block leading-tight">Biaya Kursus</span>
                            <span class="font-heading font-black text-lg text-pepsi-navy"><?= formatRupiah($prog['price']) ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="openSyllabusModal(<?= htmlspecialchars(json_encode([
                                'id' => $prog['id'],
                                'title' => $prog['title'],
                                'code' => $prog['code'],
                                'level_label' => $prog['level_label'],
                                'price' => formatRupiah($prog['price']),
                                'english_ratio' => $prog['english_ratio'],
                                'description' => $prog['description'],
                                'syllabus' => $syllabusList,
                                'final_exam' => $prog['final_exam']
                            ])) ?>)" 
                            class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition">
                                Detail Silabus
                            </button>
                            <button @click="toggleCalculatorProgram(<?= $prog['id'] ?>, '<?= addslashes($prog['title']) ?>', <?= $prog['price'] ?>)" 
                                    :class="isProgramSelected(<?= $prog['id'] ?>) ? 'bg-pepsi-red text-white' : 'bg-pepsi-blue text-white'"
                                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition shadow-md">
                                <span x-text="isProgramSelected(<?= $prog['id'] ?>) ? 'Batalkan' : '+ Pilih'"></span>
                            </button>
                        </div>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SECTION 2: CEK JADWAL & RUANGAN -->
    <section id="jadwal" class="py-20 bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
                <div>
                    <span class="text-xs font-extrabold uppercase tracking-widest text-pepsi-blue">LIVE TIMETABLE</span>
                    <h2 class="font-heading font-black text-3xl sm:text-4xl text-pepsi-navy mt-1">Cek Jadwal & Ruangan</h2>
                    <p class="text-slate-600 text-sm mt-1">
                        Matriks sesi jam kelas, tutor pendamping, dan lokasi ruangan di Kampus Pare.
                    </p>
                </div>
                <!-- Filters dropdown -->
                <div class="flex flex-wrap items-center gap-3">
                    <select x-model="filterTime" class="bg-white border border-slate-200 text-slate-800 text-xs sm:text-sm font-semibold rounded-xl px-3 py-2.5 shadow-sm focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                        <option value="all">Semua Jam Sesi</option>
                        <?php foreach ($timeSlots as $slot): ?>
                        <option value="<?= $slot ?>"><?= $slot ?> WIB</option>
                        <?php endforeach; ?>
                    </select>

                    <select x-model="filterCoach" class="bg-white border border-slate-200 text-slate-800 text-xs sm:text-sm font-semibold rounded-xl px-3 py-2.5 shadow-sm focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                        <option value="all">Semua Coach</option>
                        <?php foreach ($tutors as $tutor): ?>
                        <option value="<?= $tutor['name'] ?>">Coach <?= $tutor['name'] ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select x-model="filterRoom" class="bg-white border border-slate-200 text-slate-800 text-xs sm:text-sm font-semibold rounded-xl px-3 py-2.5 shadow-sm focus:outline-none focus:ring-2 focus:ring-pepsi-blue">
                        <option value="all">Semua Ruangan</option>
                        <?php foreach ($rooms as $room): ?>
                        <option value="<?= $room['name'] ?>">Ruang <?= $room['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Schedule Table Container (Responsive Horizontal Scroll for Android) -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-pepsi-navy text-white text-xs uppercase font-bold tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Jam Sesi</th>
                                <th class="px-6 py-4">Program & Kode</th>
                                <th class="px-6 py-4">Level</th>
                                <th class="px-6 py-4">Coach / Tutor</th>
                                <th class="px-6 py-4">Ruang Kelas</th>
                                <th class="px-6 py-4 text-right">Biaya</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <?php foreach ($schedules as $sched): ?>
                            <tr x-show="(filterTime === 'all' || filterTime === '<?= $sched['time_slot'] ?>') &&
                                        (filterCoach === 'all' || filterCoach === '<?= $sched['tutor_name'] ?>') &&
                                        (filterRoom === 'all' || filterRoom === '<?= $sched['room_name'] ?>')"
                                class="hover:bg-blue-50/40 transition">
                                <td class="px-6 py-4 font-bold text-pepsi-blue whitespace-nowrap">
                                    <?= $sched['time_slot'] ?> WIB
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-900 block"><?= sanitize($sched['program_title']) ?></span>
                                    <span class="text-xs text-slate-400 font-mono"><?= sanitize($sched['program_code']) ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $sched['orientation'] === 'ielts' ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-700' ?>">
                                        <?= sanitize($sched['level_label']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-700 font-semibold">
                                    Coach <?= sanitize($sched['tutor_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 rounded-xl bg-slate-100 text-slate-800 text-xs font-bold">
                                        Ruang <?= sanitize($sched['room_name']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-heading font-black text-pepsi-navy whitespace-nowrap">
                                    <?= formatRupiah($sched['price']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: KALKULATOR BIAYA & DAFTAR VIA WA -->
    <section id="kalkulator" class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="text-xs font-extrabold uppercase tracking-widest text-pepsi-red">INTERACTIVE CALCULATOR</span>
                <h2 class="font-heading font-black text-3xl sm:text-4xl text-pepsi-navy mt-1">Kalkulator Biaya & Pendaftaran</h2>
                <p class="text-slate-600 text-sm mt-1">
                    Pilih satu atau beberapa program kursus sekaligus, sistem akan menghitung total biaya secara otomatis dan membuat format pendaftaran resmi ke WhatsApp Admin.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left: Multi Program Selector -->
                <div class="lg:col-span-7 bg-slate-50 p-6 sm:p-8 rounded-3xl border border-slate-200 space-y-4">
                    <h3 class="font-heading font-bold text-lg text-slate-900 flex items-center justify-between">
                        <span>Pilih Program Kursus (Bisa Lebih dari 1)</span>
                        <span class="text-xs text-pepsi-blue font-bold font-sans" x-text="selectedPrograms.length + ' program dipilih'"></span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[460px] overflow-y-auto pr-2">
                        <?php foreach ($programs as $prog): ?>
                        <label class="flex items-start gap-3 p-3.5 rounded-2xl bg-white border transition cursor-pointer select-none"
                               :class="isProgramSelected(<?= $prog['id'] ?>) ? 'border-pepsi-blue ring-2 ring-blue-500/20 bg-blue-50/30' : 'border-slate-200 hover:border-slate-300'">
                            <input type="checkbox" 
                                   class="mt-1 w-4 h-4 text-pepsi-blue rounded focus:ring-pepsi-blue"
                                   :checked="isProgramSelected(<?= $prog['id'] ?>)"
                                   @change="toggleCalculatorProgram(<?= $prog['id'] ?>, '<?= addslashes($prog['title']) ?>', <?= $prog['price'] ?>)">
                            <div class="text-xs flex-1">
                                <span class="font-bold text-slate-900 block"><?= sanitize($prog['title']) ?></span>
                                <span class="text-[11px] text-slate-500 block"><?= sanitize($prog['level_label']) ?> &bull; <?= sanitize($prog['orientation']) === 'ielts' ? 'IELTS' : 'Speaking' ?></span>
                                <span class="font-heading font-extrabold text-pepsi-blue text-sm block mt-1"><?= formatRupiah($prog['price']) ?></span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right: Calculator Summary & WhatsApp Registration Form -->
                <div id="daftar" class="lg:col-span-5 bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-xl space-y-5">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">TOTAL ESTIMASI</span>
                            <div class="font-heading font-black text-3xl text-pepsi-navy" x-text="formattedTotal">Rp 0</div>
                        </div>
                        <button x-show="selectedPrograms.length > 0" @click="clearPrograms()" class="text-xs text-red-500 font-bold hover:underline">
                            Reset Pilihan
                        </button>
                    </div>

                    <!-- Selected Badges -->
                    <div class="space-y-2">
                        <span class="text-xs font-bold text-slate-500 block">Rincian Program Terpilih:</span>
                        <template x-if="selectedPrograms.length === 0">
                            <p class="text-xs text-slate-400 italic">Belum ada program yang dipilih. Silakan centang program di samping.</p>
                        </template>
                        <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                            <template x-for="p in selectedPrograms" :key="p.id">
                                <div class="flex items-center justify-between text-xs p-2 rounded-xl bg-slate-50 border border-slate-100">
                                    <span class="font-bold text-slate-800" x-text="p.title"></span>
                                    <span class="text-pepsi-blue font-extrabold" x-text="formatRp(p.price)"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Registration Form Fields -->
                    <form @submit.prevent="submitWhatsAppRegistration()" class="space-y-3.5 pt-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Periode Belajar *</label>
                            <select x-model="regPeriod" required class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 focus:bg-white focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                                <?php if (!empty($upcomingPeriods)): ?>
                                <?php foreach ($upcomingPeriods as $up): ?>
                                <option value="<?= sanitize($up['name']) ?>"><?= sanitize($up['name']) ?> (mulai <?= sanitize(tanggalIndo($up['start_date'])) ?>)</option>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <option value="Periode 10 (Mulai Terdekat)">Periode 10 (Mulai Terdekat)</option>
                                <option value="Periode 10 Bulan Ini">Periode 10 Bulan Ini</option>
                                <option value="Periode 25 Bulan Ini">Periode 25 Bulan Ini</option>
                                <option value="Periode 10 Bulan Depan">Periode 10 Bulan Depan</option>
                                <option value="Periode 25 Bulan Depan">Periode 25 Bulan Depan</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Pilihan Jam Sesi Kelas *</label>
                            <select x-model="regTimeSlot" required class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 focus:bg-white focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                                <option value="">-- Pilih Jam Kelas yang Diinginkan --</option>
                                <?php foreach ($timeSlots as $slot): ?>
                                <option value="<?= $slot ?> WIB"><?= $slot ?> WIB</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                                <input type="text" x-model="regName" required placeholder="Contoh: Budi Pratama" class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 focus:bg-white focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp *</label>
                                <input type="tel" x-model="regPhone" required placeholder="081234567890" class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 focus:bg-white focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Kota Asal / Domisili *</label>
                            <input type="text" x-model="regCity" required placeholder="Contoh: Surabaya / Jakarta / Pare" class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 focus:bg-white focus:ring-2 focus:ring-pepsi-blue focus:outline-none">
                        </div>

                        <button type="submit" 
                                :disabled="selectedPrograms.length === 0"
                                :class="selectedPrograms.length === 0 ? 'bg-slate-300 cursor-not-allowed text-slate-500' : 'bg-pepsi-wa hover:bg-pepsi-wa-dark text-white shadow-lg shadow-green-500/30 hover:-translate-y-0.5'"
                                class="w-full py-3.5 px-4 rounded-2xl font-bold text-sm transition-all flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.299.144.347.491 1.199.534 1.286.043.087.072.188.014.303-.058.116-.087.188-.173.289l-.26.302c-.087.087-.179.182-.077.357.101.174.45 1.084 1.144 1.702.89.794 1.64 1.04 1.871 1.155.231.116.368.101.505-.058.137-.159.587-.683.743-.918.156-.235.312-.196.526-.117.214.079 1.36.642 1.594.757.234.115.39.173.448.271.058.098.058.568-.086.973z"/></svg>
                            <span>Daftar via WhatsApp Sekarang</span>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </section>

    <!-- SECTION 4: PEPSI FOOD & DRINK & FASILITAS HIGHLIGHT -->
    <section class="py-16 bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 text-pepsi-blue flex items-center justify-center font-bold text-xl flex-shrink-0">
                        ☕
                    </div>
                    <div>
                        <h4 class="font-heading font-bold text-base text-slate-900">Pepsi Food &amp; Drink</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Tersedia snack, kopi dingin, makanan berat, dan alat tulis dengan pembayaran kasir cepat Tunai &amp; Cash.
                        </p>
                    </div>
                </div>

                <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-100 text-pepsi-red flex items-center justify-center font-bold text-xl flex-shrink-0">
                        ❄️
                    </div>
                    <div>
                        <h4 class="font-heading font-bold text-base text-slate-900">Ruangan Kelas Nyaman Ber-AC</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Semua ruang kelas (Alpha, Bravo, Charlie, Deluxe, Romeo, Gazebo) berfasilitas lengkap untuk diskusi intensif.
                        </p>
                    </div>
                </div>

                <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-green-100 text-green-700 flex items-center justify-center font-bold text-xl flex-shrink-0">
                        📜
                    </div>
                    <div>
                        <h4 class="font-heading font-bold text-base text-slate-900">Sertifikat Resmi Terdaftar</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Terakreditasi SK Diknas No. 421.9/3735/418.20/2026 & NPSN K9997239 di Pare, Kediri.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SYLLABUS MODAL POPUP -->
    <div x-show="syllabusModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="syllabusModalOpen = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-100 relative space-y-5 animate-in fade-in zoom-in-95 duration-200">
            <button @click="syllabusModalOpen = false" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 p-1.5 rounded-full hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-pepsi-blue" x-text="modalData.level_label"></span>
                <h3 class="font-heading font-black text-2xl text-pepsi-navy mt-2" x-text="modalData.title + ' (' + modalData.code + ')'"></h3>
                <p class="text-slate-600 text-xs sm:text-sm mt-1" x-text="modalData.description"></p>
            </div>

            <!-- Syllabus topics list -->
            <div class="space-y-2">
                <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Silabus Materi Utama:</h5>
                <ul class="space-y-1.5 text-xs text-slate-700 font-medium">
                    <template x-for="(topic, idx) in modalData.syllabus" :key="idx">
                        <li class="flex items-start gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <span class="text-pepsi-red font-bold">&bull;</span>
                            <span x-text="topic"></span>
                        </li>
                    </template>
                </ul>
            </div>

            <!-- Exam -->
            <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900">
                <span class="font-bold block uppercase tracking-wider text-[10px] text-amber-700 mb-0.5">Ujian Akhir Kelulusan:</span>
                <span class="font-semibold" x-text="modalData.final_exam"></span>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <div>
                    <span class="text-[11px] text-slate-400 block">Biaya Kursus</span>
                    <span class="font-heading font-black text-xl text-pepsi-navy" x-text="modalData.price"></span>
                </div>
                <button @click="toggleCalculatorProgram(modalData.id, modalData.title, unformatRp(modalData.price)); syllabusModalOpen = false;" class="bg-pepsi-blue text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-md hover:bg-blue-900 transition">
                    Pilih & Masukkan Kalkulator &rarr;
                </button>
            </div>
        </div>
    </div>

</div>

<!-- Interactive Alpine Logic -->
<script>
function guestApp() {
    return {
        orient: 'all',
        level: 'all',
        filterTime: 'all',
        filterCoach: 'all',
        filterRoom: 'all',
        
        // Calculator state
        selectedPrograms: [],
        
        // Form state
        regPeriod: <?= json_encode($nextPeriod['name'] ?? 'Periode 10 (Mulai Terdekat)') ?>,
        regTimeSlot: '',
        regName: '',
        regPhone: '',
        regCity: '',

        // Modal state
        syllabusModalOpen: false,
        modalData: {
            id: 0,
            title: '',
            code: '',
            level_label: '',
            price: '',
            english_ratio: '',
            description: '',
            syllabus: [],
            final_exam: ''
        },

        get totalCost() {
            return this.selectedPrograms.reduce((sum, p) => sum + p.price, 0);
        },

        get formattedTotal() {
            return this.formatRp(this.totalCost);
        },

        isProgramSelected(id) {
            return this.selectedPrograms.some(p => p.id === id);
        },

        toggleCalculatorProgram(id, title, price) {
            const idx = this.selectedPrograms.findIndex(p => p.id === id);
            if (idx >= 0) {
                this.selectedPrograms.splice(idx, 1);
            } else {
                this.selectedPrograms.push({ id, title, price });
            }
        },

        clearPrograms() {
            this.selectedPrograms = [];
        },

        openSyllabusModal(data) {
            this.modalData = data;
            this.syllabusModalOpen = true;
        },

        formatRp(val) {
            return 'Rp ' + Number(val).toLocaleString('id-ID');
        },

        unformatRp(str) {
            if (typeof str === 'number') return str;
            return parseInt(str.replace(/[^0-9]/g, '')) || 0;
        },

        // Registration success modal state
        regSuccessModal: false,
        regResult: {
            registration_no: '',
            student_name: '',
            total_fee: 0,
            wa_url: ''
        },
        isSubmitting: false,

        async submitWhatsAppRegistration() {
            if (this.selectedPrograms.length === 0) {
                alert('Silakan pilih minimal 1 program kursus.');
                return;
            }
            if (!this.regTimeSlot) {
                alert('Silakan pilih Jam Sesi Kelas.');
                return;
            }

            this.isSubmitting = true;
            const payload = {
                student_name: this.regName,
                phone: this.regPhone,
                city: this.regCity,
                period_name: this.regPeriod,
                time_slot: this.regTimeSlot,
                programs: this.selectedPrograms
            };

            try {
                const response = await fetch('<?= url('register') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const res = await response.json();

                if (res.success) {
                    this.regResult = res;
                    this.regSuccessModal = true;
                    // Automatically open WhatsApp in new tab
                    window.open(res.wa_url, '_blank');
                } else {
                    alert('Gagal mengirim pendaftaran: ' + (res.error || 'Terjadi kesalahan sistem'));
                }
            } catch (err) {
                console.error(err);
                alert('Koneksi terputus saat mengirim pendaftaran.');
            } finally {
                this.isSubmitting = false;
            }
        }
    };
}
</script>

    <!-- SUCCESS REGISTRATION MODAL POPUP -->
    <div x-show="regSuccessModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-100 text-center space-y-4 animate-in fade-in zoom-in-95 duration-200">
            <div class="w-16 h-16 rounded-full bg-green-100 text-green-700 flex items-center justify-center mx-auto text-2xl font-bold">
                &check;
            </div>

            <div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 inline-block mb-2">
                    Menunggu Approval Admin
                </span>
                <h3 class="font-heading font-black text-2xl text-slate-900">Pendaftaran Berhasil Dicatat!</h3>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                    Data calon siswa telah tersimpan di sistem administrasi MR.PEPSI UPGRADE.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-left space-y-2 text-xs">
                <div class="flex justify-between items-center pb-2 border-b border-slate-200">
                    <span class="text-slate-500 font-semibold">Nomor Pendaftaran:</span>
                    <span class="font-mono font-black text-pepsi-blue text-sm" x-text="regResult.registration_no"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Nama Siswa:</span>
                    <span class="font-bold text-slate-800" x-text="regResult.student_name"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Total Biaya Kursus:</span>
                    <span class="font-heading font-black text-pepsi-navy text-sm" x-text="formatRp(regResult.total_fee)"></span>
                </div>
            </div>

            <div class="space-y-2 pt-2">
                <a :href="regResult.wa_url" target="_blank" class="w-full py-3.5 bg-pepsi-wa hover:bg-pepsi-wa-dark text-white font-bold text-xs rounded-xl shadow-lg shadow-green-500/25 flex items-center justify-center gap-2 transition">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.299.144.347.491 1.199.534 1.286.043.087.072.188.014.303-.058.116-.087.188-.173.289l-.26.302c-.087.087-.179.182-.077.357.101.174.45 1.084 1.144 1.702.89.794 1.64 1.04 1.871 1.155.231.116.368.101.505-.058.137-.159.587-.683.743-.918.156-.235.312-.196.526-.117.214.079 1.36.642 1.594.757.234.115.39.173.448.271.058.098.058.568-.086.973z"/></svg>
                    <span>Kirim Pesan Konfirmasi ke WA Admin</span>
                </a>
                <button @click="regSuccessModal = false; clearPrograms(); regName = ''; regPhone = ''; regCity = '';" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                    Tutup & Selesai
                </button>
            </div>
        </div>
    </div>
