<?php

namespace App\Support;

class ChatbotMatcher
{
    /**
     * @param array{
     *  last_intent_key?:string|null,
     *  last_menu_key?:string|null,
     *  role_menus?:array<int, array{key:string,name:string,url:string}>
     * } $context
     * @return array{
     *  reply:string,
     *  intent_key:?string,
     *  confidence:?float,
     *  menu_key:?string
     * }
     */
    public function reply(string $message, string $role, string $lang = 'id', array $context = []): array
    {
        $normalizedRole = $this->normalizeRole($role);
        $text = $this->normalizeText($message);
        $roleMenus = $context['role_menus'] ?? [];

        if ($text === '') {
            return $this->out($this->messageByLang('Silakan tulis pertanyaan Anda dulu.', 'Please type your question first.', $lang));
        }

        if ($this->isForbiddenDataRequest($text)) {
            return $this->out(
                $this->buildAccessDeniedReply($normalizedRole, $lang),
                'dynamic.access_denied',
                1.0,
                null
            );
        }

        if ($this->isBypassOrManipulationQuestion($text)) {
            return $this->out(
                $this->messageByLang(
                    'Maaf, saya tidak dapat membantu permintaan tersebut. Saya hanya dapat membantu penggunaan aplikasi Absensi PKL secara resmi.',
                    'Sorry, I cannot help with that request. I can only help with official Absensi PKL application usage.',
                    $lang
                ),
                'dynamic.security_denied',
                1.0,
                null
            );
        }

        if ($this->isSimpleSmallTalk($text)) {
            return $this->out($this->buildSmallTalkReply($normalizedRole, $lang), 'dynamic.smalltalk', 1.0, null);
        }

        if ($this->isCapabilityQuestion($text)) {
            return $this->out($this->buildCapabilityReply($normalizedRole, $roleMenus, $lang), 'dynamic.capability', 1.0, null);
        }

        if ($this->isGreetingQuestion($text)) {
            return $this->out($this->buildGreetingReply($lang), 'dynamic.greeting', 1.0, null);
        }

        if ($this->isRoleDutyQuestion($text)) {
            return $this->out($this->buildRoleDutyReply($normalizedRole, $lang), 'dynamic.role_duty', 1.0, null);
        }

        if ($this->isFollowUpQuestion($text)) {
            $contextMenu = $this->menuFromContext($context, $roleMenus);
            if ($contextMenu !== null) {
                $detail = $this->buildMenuDetailedReply($contextMenu['key'], $lang);
                $howTo = $this->buildMenuHowToReply($contextMenu['key'], $lang);

                return $this->out(
                    $detail."\n\n".$howTo,
                    'dynamic.follow_up',
                    1.0,
                    $contextMenu['key']
                );
            }

            return $this->out(
                $this->messageByLang(
                    'Siap. Sebutkan nama menu yang ingin dilanjutkan, misalnya: "jelaskan lebih detail menu Absensi Harian".',
                    'Sure. Mention the menu name you want to continue, for example: "explain Daily Attendance in more detail".',
                    $lang
                ),
                'dynamic.follow_up_need_menu',
                1.0,
                null
            );
        }

        if ($this->isMenuListQuestion($text)) {
            return $this->out($this->buildRoleMenuListReply($roleMenus, $lang), 'dynamic.menu_list', 1.0, null);
        }

        $targetMenu = $this->resolveTargetMenu($text, $roleMenus) ?? $this->menuFromContext($context, $roleMenus);
        if ($targetMenu !== null) {
            if ($this->isWhereQuestion($text)) {
                return $this->out(
                    $this->buildMenuLocationReply($targetMenu, $lang),
                    'dynamic.where',
                    1.0,
                    $targetMenu['key']
                );
            }
            if ($this->isHowToQuestion($text)) {
                return $this->out(
                    $this->buildMenuHowToReply($targetMenu['key'], $lang),
                    'dynamic.how_to',
                    1.0,
                    $targetMenu['key']
                );
            }
            if ($this->isDetailQuestion($text)) {
                return $this->out(
                    $this->buildMenuDetailedReply($targetMenu['key'], $lang),
                    'dynamic.detail',
                    1.0,
                    $targetMenu['key']
                );
            }
            if ($this->isPurposeQuestion($text)) {
                return $this->out(
                    $this->buildMenuPurposeReply($targetMenu['key'], $lang),
                    'dynamic.purpose',
                    1.0,
                    $targetMenu['key']
                );
            }
        }

        if ($this->isDetailQuestion($text)) {
            $contextMenu = $this->menuFromContext($context, $roleMenus);
            if ($contextMenu !== null) {
                return $this->out(
                    $this->buildMenuDetailedReply($contextMenu['key'], $lang),
                    'dynamic.detail',
                    1.0,
                    $contextMenu['key']
                );
            }
        }

        $kbMatch = $this->bestKnowledgeMatch($text);
        if ($kbMatch !== null) {
            $item = $kbMatch['item'];
            $score = $kbMatch['score'];

            if (! $this->isRoleAllowed($normalizedRole, $item['allowed_roles'])) {
                $roleSafeKbMatch = $this->bestKnowledgeMatchForRole($text, $normalizedRole);
                if ($roleSafeKbMatch !== null) {
                    $safeItem = $roleSafeKbMatch['item'];
                    $safeScore = $roleSafeKbMatch['score'];
                    $safeMenuKey = (string) ($safeItem['menu_key'] ?? '');
                    $safeMenu = $safeMenuKey !== '' ? $this->findMenuByKey($roleMenus, $safeMenuKey) : null;
                    $safeBaseReply = $this->messageByLang((string) $safeItem['answer_id'], (string) $safeItem['answer_en'], $lang);
                    $safeFinal = $safeMenu ? $safeBaseReply."\n\n".$this->buildMenuLocationReply($safeMenu, $lang) : $safeBaseReply;

                    return $this->out(
                        $safeFinal,
                        (string) $safeItem['key'],
                        round(min(1, $safeScore / 4), 4),
                        $safeMenuKey !== '' ? $safeMenuKey : null
                    );
                }

                return $this->out(
                    $this->buildAccessDeniedReply($normalizedRole, $lang),
                    (string) $item['key'],
                    round(min(1, $score / 4), 4),
                    null
                );
            }

            $menuKey = (string) ($item['menu_key'] ?? '');
            $menu = $menuKey !== '' ? $this->findMenuByKey($roleMenus, $menuKey) : null;
            $baseReply = $this->messageByLang((string) $item['answer_id'], (string) $item['answer_en'], $lang);
            $final = $menu ? $baseReply."\n\n".$this->buildMenuLocationReply($menu, $lang) : $baseReply;

            return $this->out(
                $final,
                (string) $item['key'],
                round(min(1, $score / 4), 4),
                $menuKey !== '' ? $menuKey : null
            );
        }

        if ($this->looksLikeWebsiteQuestion($text)) {
            return $this->out(
                $this->buildGenericWebsiteAnswer($roleMenus, $normalizedRole, $lang),
                'dynamic.website_help',
                0.55,
                null
            );
        }

        return $this->out(
            $this->buildOutOfScopeReply($lang),
            'dynamic.out_of_scope',
            1.0,
            null
        );
    }

    /**
     * @return array{reply:string,intent_key:?string,confidence:?float,menu_key:?string}
     */
    private function out(string $reply, ?string $intentKey = null, ?float $confidence = null, ?string $menuKey = null): array
    {
        return [
            'reply' => $reply,
            'intent_key' => $intentKey,
            'confidence' => $confidence,
            'menu_key' => $menuKey,
        ];
    }

    private function normalizeRole(string $role): string
    {
        return match ($role) {
            'owner' => 'kepsek',
            'operator' => 'admin_sekolah',
            default => $role,
        };
    }

    private function normalizeText(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = $this->fixCommonTypos($text);
        return trim($text);
    }

    private function fixCommonTypos(string $text): string
    {
        $map = [
            'jlasn' => 'jelasin',
            'jelasn' => 'jelasin',
            'dmana' => 'dimana',
            'dmn' => 'dimana',
            'webiste' => 'website',
            'weebsite' => 'website',
            'seting' => 'setting',
            'settng' => 'setting',
            'hakakses' => 'hak akses',
            'valdasi' => 'validasi',
            'absnsi' => 'absensi',
            'pengjuan' => 'pengajuan',
            'fiturr' => 'fitur',
            'mneu' => 'menu',
            'aplikasii' => 'aplikasi',
            'websait' => 'website',
        ];

        $parts = explode(' ', $text);
        foreach ($parts as $i => $part) {
            $parts[$i] = $map[$part] ?? $part;
        }

        return implode(' ', $parts);
    }

    private function isMenuListQuestion(string $text): bool
    {
        return str_contains($text, 'menu saya')
            || str_contains($text, 'menu apa')
            || str_contains($text, 'fitur saya')
            || str_contains($text, 'what menus')
            || str_contains($text, 'available menu')
            || str_contains($text, 'my menus');
    }

    private function isGreetingQuestion(string $text): bool
    {
        $greetings = ['hi', 'hai', 'halo', 'hello', 'pagi', 'siang', 'sore', 'malam', 'hey'];
        foreach ($greetings as $word) {
            if ($text === $word
                || str_starts_with($text, $word.' ')
                || str_contains($text, $word.' bot')
                || str_contains($text, $word.' chatbot')) {
                return true;
            }
        }

        return false;
    }

    private function isSimpleSmallTalk(string $text): bool
    {
        return in_array($text, ['ok', 'oke', 'siap', 'thanks', 'thank you', 'makasih', 'terima kasih', 'sip'], true)
            || str_contains($text, 'terima kasih')
            || str_contains($text, 'makasih');
    }

    private function isCapabilityQuestion(string $text): bool
    {
        return str_contains($text, 'apa yang bisa kamu lakukan')
            || str_contains($text, 'apa yang bisa bot lakukan')
            || str_contains($text, 'apa guna kamu')
            || str_contains($text, 'apa guan kamu')
            || str_contains($text, 'guna kamu')
            || str_contains($text, 'kamu bisa apa')
            || str_contains($text, 'bisa bantu apa')
            || str_contains($text, 'bisa ngapain aja')
            || str_contains($text, 'ngapain aja')
            || str_contains($text, 'fitur apa yang bisa dibantu')
            || str_contains($text, 'what can you do');
    }

    private function isRoleDutyQuestion(string $text): bool
    {
        return str_contains($text, 'apa tugas saya')
            || str_contains($text, 'tugas saya apa')
            || str_contains($text, 'peran saya apa')
            || str_contains($text, 'role saya apa')
            || str_contains($text, 'what is my role')
            || str_contains($text, 'what are my tasks')
            || str_contains($text, 'my responsibilities');
    }

    private function isFollowUpQuestion(string $text): bool
    {
        return str_contains($text, 'jelasin lagi')
            || str_contains($text, 'jelaskan lagi')
            || str_contains($text, 'lebih detail')
            || str_contains($text, 'lebih rinci')
            || str_contains($text, 'lebih lengkap')
            || str_contains($text, 'contohnya')
            || str_contains($text, 'kasih contoh')
            || str_contains($text, 'lanjut')
            || str_contains($text, 'lanjutkan')
            || str_contains($text, 'coba detail')
            || str_contains($text, 'detail dong');
    }

    private function isForbiddenDataRequest(string $text): bool
    {
        $patterns = [
            'nis teman',
            'nama teman',
            'data siswa lain',
            'siswa lain',
            'user lain',
            'data guru',
            'nuptk',
            'nip',
            'siapa admin',
            'siapa superadmin',
            'tempat pkl teman',
            'pembimbing pkl lain',
            'akses role lain',
            'hak role lain',
            'semua akun',
            'akun lain',
            'struktur backend',
            'data internal',
            'siapa developer',
            'siapa yang buat sistem',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($text, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function isBypassOrManipulationQuestion(string $text): bool
    {
        $patterns = [
            'bypass',
            'hack',
            'retas',
            'bobol',
            'manipulasi',
            'ubah data orang lain',
            'naikkan akses',
            'ambil alih akun',
            'sql injection',
            'exploit',
            'token orang lain',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($text, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function isWhereQuestion(string $text): bool
    {
        return str_contains($text, 'dimana')
            || str_contains($text, 'di mana')
            || str_contains($text, 'letaknya')
            || str_contains($text, 'where');
    }

    private function isHowToQuestion(string $text): bool
    {
        return str_contains($text, 'cara pakai')
            || str_contains($text, 'cara menggunakan')
            || str_contains($text, 'cara gunakan')
            || str_contains($text, 'cara ')
            || str_contains($text, 'gimana pakainya')
            || str_contains($text, 'gimana cara')
            || str_contains($text, 'bagaimana')
            || str_contains($text, 'how to')
            || str_contains($text, 'steps');
    }

    private function isPurposeQuestion(string $text): bool
    {
        return str_contains($text, 'untuk apa')
            || str_contains($text, 'untuk apa saja')
            || str_contains($text, 'fungsinya')
            || str_contains($text, 'fungsi')
            || str_contains($text, 'kegunaan')
            || str_contains($text, 'buat apa')
            || str_contains($text, 'what is')
            || str_contains($text, 'purpose');
    }

    private function isDetailQuestion(string $text): bool
    {
        return str_contains($text, 'lebih detail')
            || str_contains($text, 'lebih rinci')
            || str_contains($text, 'lebih lengkap')
            || str_contains($text, 'jelaskan')
            || str_contains($text, 'jelasin')
            || str_contains($text, 'jlasn')
            || str_contains($text, 'detailnya')
            || str_contains($text, 'rincian')
            || str_contains($text, 'more detail')
            || str_contains($text, 'explain more');
    }

    private function looksLikeWebsiteQuestion(string $text): bool
    {
        return str_contains($text, 'website')
            || str_contains($text, 'aplikasi')
            || str_contains($text, 'menu')
            || str_contains($text, 'fitur')
            || str_contains($text, 'validasi')
            || str_contains($text, 'dashboard')
            || str_contains($text, 'setting')
            || str_contains($text, 'profil')
            || str_contains($text, 'absensi')
            || str_contains($text, 'pengajuan')
            || str_contains($text, 'laporan');
    }

    /**
     * @param array<int, array{key:string,name:string,url:string}> $menus
     * @return array{key:string,name:string,url:string}|null
     */
    private function resolveTargetMenu(string $text, array $menus): ?array
    {
        $best = null;
        $bestScore = 0.0;

        foreach ($menus as $menu) {
            $aliases = $this->menuAliases($menu);
            $score = 0.0;
            foreach ($aliases as $alias) {
                $score += $this->phraseFuzzyScore($text, $alias);
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $menu;
            }
        }

        return $bestScore >= 0.95 ? $best : null;
    }

    /**
     * @param array{last_menu_key?:string|null} $context
     * @param array<int, array{key:string,name:string,url:string}> $menus
     * @return array{key:string,name:string,url:string}|null
     */
    private function menuFromContext(array $context, array $menus): ?array
    {
        $key = trim((string) ($context['last_menu_key'] ?? ''));
        if ($key === '') {
            return null;
        }
        return $this->findMenuByKey($menus, $key);
    }

    /**
     * @param array<int, array{key:string,name:string,url:string}> $menus
     * @return array{key:string,name:string,url:string}|null
     */
    private function findMenuByKey(array $menus, string $key): ?array
    {
        foreach ($menus as $menu) {
            if ($menu['key'] === $key) {
                return $menu;
            }
        }
        return null;
    }

    /**
     * @param array{key:string,name:string,url:string} $menu
     * @return array<int, string>
     */
    private function menuAliases(array $menu): array
    {
        $aliases = [
            $this->normalizeText($menu['name']),
            $this->normalizeText($menu['key']),
            $this->normalizeText(trim($menu['url'], '/')),
        ];

        $extra = [
            'fitur/hak-akses-menu' => ['hak akses', 'hak akses menu', 'permission'],
            'fitur/setting-web' => ['setting website', 'setting web'],
            'summary-report' => ['validasi mingguan', 'weekly validation', 'weekly notes', 'catatan mingguan'],
            'summary-report/rekap' => ['rekap mingguan', 'weekly recap'],
            'summary-report/analisis' => ['monitoring progres', 'analisis mingguan'],
        ];
        foreach (($extra[$menu['key']] ?? []) as $alias) {
            $aliases[] = $this->normalizeText($alias);
        }

        return array_values(array_unique(array_filter($aliases, fn (string $x): bool => $x !== '')));
    }

    /**
     * @param array<int, string> $keywords
     */
    private function score(string $text, array $keywords): float
    {
        $score = 0.0;
        foreach ($keywords as $keyword) {
            $needle = $this->normalizeText($keyword);
            $score += $this->phraseFuzzyScore($text, $needle);
        }
        return $score;
    }

    /**
     * @return array{item:array<string,mixed>,score:float}|null
     */
    private function bestKnowledgeMatch(string $text): ?array
    {
        $best = null;
        $bestScore = 0.0;
        foreach (ChatbotKnowledgeBase::items() as $item) {
            $score = $this->score($text, $item['keywords']);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $item;
            }
        }

        if ($best === null || $bestScore < 0.9) {
            return null;
        }

        return ['item' => $best, 'score' => $bestScore];
    }

    /**
     * @return array{item:array<string,mixed>,score:float}|null
     */
    private function bestKnowledgeMatchForRole(string $text, string $role): ?array
    {
        $best = null;
        $bestScore = 0.0;

        foreach (ChatbotKnowledgeBase::items() as $item) {
            if (! $this->isRoleAllowed($role, $item['allowed_roles'])) {
                continue;
            }

            $score = $this->score($text, $item['keywords']);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $item;
            }
        }

        if ($best === null || $bestScore < 0.9) {
            return null;
        }

        return ['item' => $best, 'score' => $bestScore];
    }

    /**
     * @param array<int, string> $allowedRoles
     */
    private function isRoleAllowed(string $role, array $allowedRoles): bool
    {
        if ($role === 'superadmin') {
            return true;
        }
        return in_array('*', $allowedRoles, true) || in_array($role, $allowedRoles, true);
    }

    private function messageByLang(string $id, string $en, string $lang): string
    {
        return $lang === 'en' ? $en : $id;
    }

    /**
     * @param array<int, array{key:string,name:string,url:string}> $menus
     */
    private function buildRoleMenuListReply(array $menus, string $lang): string
    {
        if ($menus === []) {
            return $this->messageByLang(
                'Menu untuk role Anda belum ditemukan. Minta superadmin cek Hak Akses Menu.',
                'No menus found for your role. Ask superadmin to review Menu Permissions.',
                $lang
            );
        }
        $rows = array_map(fn (array $m): string => '- '.$m['name'].' ('.$m['url'].')', array_slice($menus, 0, 20));
        return $this->messageByLang('Menu yang bisa Anda akses saat ini:', 'Menus you can access right now:', $lang)."\n".implode("\n", $rows);
    }

    /**
     * @param array{key:string,name:string,url:string} $menu
     */
    private function buildMenuLocationReply(array $menu, string $lang): string
    {
        return $this->messageByLang(
            'Letaknya di menu `'.$menu['name'].'` dengan URL `'.$menu['url'].'`.',
            'It is located at `'.$menu['name'].'` menu with URL `'.$menu['url'].'`.',
            $lang
        );
    }

    private function buildMenuPurposeReply(string $menuKey, string $lang): string
    {
        $guide = MenuGuideCatalog::get($menuKey);
        if ($guide === null) {
            return $this->messageByLang(
                'Menu ini dipakai untuk proses operasional sesuai fitur pada halaman tersebut.',
                'This menu is used for operational processes based on page features.',
                $lang
            );
        }
        return $this->messageByLang($guide['purpose_id'], $guide['purpose_en'], $lang);
    }

    private function buildMenuHowToReply(string $menuKey, string $lang): string
    {
        $guide = MenuGuideCatalog::get($menuKey);
        if ($guide === null) {
            return $this->messageByLang(
                "Cara pakai menu ini:\n1. Buka menunya.\n2. Isi data/filter.\n3. Klik aksi utama.\n4. Cek notifikasi hasil.",
                "How to use this menu:\n1. Open menu.\n2. Fill data/filter.\n3. Click main action.\n4. Check result notification.",
                $lang
            );
        }
        $steps = $lang === 'en' ? $guide['steps_en'] : $guide['steps_id'];
        return implode("\n", $steps);
    }

    private function buildMenuDetailedReply(string $menuKey, string $lang): string
    {
        $id = match ($menuKey) {
            'validasi' =>
                "Detail cara pakai Validasi Absensi:\n".
                "1. Buka menu Validasi Absensi.\n".
                "2. Pilih bucket yang sesuai: pending check-in atau pending check-out.\n".
                "3. Gunakan filter tanggal/nama agar data cepat ditemukan.\n".
                "4. Buka detail absensi lalu cek:\n".
                "   - waktu check-in/check-out,\n".
                "   - lokasi/koordinat,\n".
                "   - bukti foto,\n".
                "   - ringkasan pekerjaan (jika check-out).\n".
                "5. Tentukan aksi:\n".
                "   - Approve check-in jika data masuk valid.\n".
                "   - Approve check-out jika bukti + laporan sesuai.\n".
                "   - Reject jika tidak valid (isi alasan reject yang tepat).\n".
                "6. Setelah submit, cek tab approved/rejected untuk memastikan status sudah berpindah.",
            'fitur/hak-akses-menu' =>
                "Detail cara pakai Hak Akses Menu:\n".
                "1. Buka Hak Akses Menu.\n".
                "2. Cari nama menu di tabel.\n".
                "3. Centang kolom role yang boleh akses menu itu.\n".
                "4. Untuk membatasi akses, hilangkan centangnya.\n".
                "5. Klik Simpan Hak Akses.\n".
                "6. Minta user refresh/login ulang agar perubahan menu terbaca.",
            'summary-report' =>
                "Detail cara pakai Validasi Mingguan:\n".
                "1. Buka menu Validasi Mingguan.\n".
                "2. Pilih minggu dan siswa bila perlu.\n".
                "3. Tinjau ringkasan serta catatan mingguan saat ini.\n".
                "4. Klik tombol Tambah Catatan.\n".
                "5. Isi catatan pada pop-up lalu simpan.\n".
                "6. Pastikan catatan terbaru tampil pada panel catatan mingguan.",
            default =>
                "Rincian penggunaan menu:\n".
                "1. Buka menu yang dimaksud.\n".
                "2. Gunakan filter/pencarian bila tersedia.\n".
                "3. Buka detail data yang akan diproses.\n".
                "4. Lakukan aksi utama (Simpan/Kirim/Approve/Reject) dengan catatan jika diperlukan.\n".
                "5. Pastikan status/data berubah setelah aksi.",
        };

        $en = match ($menuKey) {
            'validasi' =>
                "Detailed Attendance Validation steps:\n".
                "1. Open Attendance Validation.\n".
                "2. Choose bucket: pending check-in or pending check-out.\n".
                "3. Use filters (date/name).\n".
                "4. Open details and review time, location, evidence, and checkout summary.\n".
                "5. Approve/Reject based on data validity.\n".
                "6. Verify status in approved/rejected tabs.",
            'summary-report' =>
                "Detailed Weekly Validation steps:\n".
                "1. Open Weekly Validation menu.\n".
                "2. Select week and student when needed.\n".
                "3. Review summary and current weekly notes.\n".
                "4. Click Add Note.\n".
                "5. Fill the note in the pop-up and save.\n".
                "6. Verify the latest note appears in the weekly notes panel.",
            default =>
                "Detailed usage:\n".
                "1. Open target menu.\n".
                "2. Use available filters/search.\n".
                "3. Open item details.\n".
                "4. Execute main action (Save/Submit/Approve/Reject).\n".
                "5. Verify data/status update.",
        };

        return $this->messageByLang($id, $en, $lang);
    }

    private function phraseFuzzyScore(string $text, string $alias): float
    {
        if ($alias === '') {
            return 0.0;
        }
        if (str_contains($text, $alias)) {
            return 1.25 + (strlen($alias) / 60);
        }

        $textTokens = $this->tokens($text);
        $aliasTokens = $this->tokens($alias);
        if ($textTokens === [] || $aliasTokens === []) {
            return 0.0;
        }

        $matched = 0;
        $matchedContent = 0;
        $contentTokens = [];
        foreach ($aliasTokens as $token) {
            if ($this->isContentToken($token)) {
                $contentTokens[] = $token;
            }
        }

        foreach ($aliasTokens as $aliasToken) {
            $bestTokenScore = 0.0;
            foreach ($textTokens as $textToken) {
                $bestTokenScore = max($bestTokenScore, $this->tokenSimilarity($aliasToken, $textToken));
            }
            if ($bestTokenScore >= 0.72) {
                $matched++;
                if ($this->isContentToken($aliasToken)) {
                    $matchedContent++;
                }
            }
        }

        $coverage = $matched / max(1, count($aliasTokens));
        if ($coverage < 0.5) {
            return 0.0;
        }

        if ($contentTokens !== [] && $matchedContent === 0) {
            return 0.0;
        }

        return $coverage * (0.95 + (count($aliasTokens) * 0.08));
    }

    private function isContentToken(string $token): bool
    {
        $t = strtolower(trim($token));
        if ($t === '') {
            return false;
        }

        $stopwords = [
            'apa', 'yang', 'itu', 'ini', 'dan', 'atau', 'di', 'ke', 'dari', 'untuk', 'pada', 'dengan', 'cara', 'bagaimana',
            'what', 'does', 'is', 'the', 'a', 'an', 'to', 'of', 'in', 'on', 'for', 'how', 'mean', 'meaning', 'status',
        ];

        if (in_array($t, $stopwords, true)) {
            return false;
        }

        return strlen($t) >= 3;
    }

    /**
     * @return array<int, string>
     */
    private function tokens(string $text): array
    {
        $parts = array_filter(explode(' ', $text), fn (string $x): bool => $x !== '');
        return array_values($parts);
    }

    private function tokenSimilarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        $maxLen = max(strlen($a), strlen($b));
        if ($maxLen === 0) {
            return 0.0;
        }

        $distance = levenshtein($a, $b);
        $ratio = 1.0 - min(1, ($distance / $maxLen));

        similar_text($a, $b, $percent);
        $similar = max(0.0, min(1.0, $percent / 100));

        return max($ratio, $similar);
    }

    /**
     * @param array<int, array{key:string,name:string,url:string}> $menus
     */
    private function buildGenericWebsiteAnswer(array $menus, string $role, string $lang): string
    {
        if ($menus === []) {
            return $this->messageByLang(
                'Saya siap bantu penggunaan website ini, tetapi menu role Anda belum terbaca. Silakan minta superadmin cek Hak Akses Menu. Anda juga bisa tanya "apa tugas saya?" untuk lihat batas akses role Anda.',
                'I can help with all website usage questions, but your role menu access is not detected yet. Please ask superadmin to check Menu Permissions.',
                $lang
            );
        }

        $topMenus = array_slice($menus, 0, 6);
        $menuList = implode(', ', array_map(fn (array $menu): string => $menu['name'], $topMenus));
        $roleScope = $this->roleScopeLabel($role, $lang);

        return $this->messageByLang(
            'Saya bisa bantu semua pertanyaan tentang website ini dalam batas role Anda ('.$roleScope.'). Menu yang bisa Anda akses: '.$menuList.'. Coba tanya seperti: "fungsi menu X", "cara pakai menu X", "dimana letak menu X", atau "jelaskan lebih detail menu X".',
            'I can help with website questions within your role scope ('.$roleScope.'). Menus I can explain now: '.$menuList.'. You can ask about menu purpose, how to use, detailed steps, or menu location.',
            $lang
        );
    }

    private function buildGreetingReply(string $lang): string
    {
        return $this->messageByLang(
            "Halo, apa yang bisa saya bantu?\n- Fungsi menu\n- Cara pakai menu\n- Letak menu\n- Alur validasi dan status",
            'Hello. I am ready to help. You can ask about menu purpose, how to use, menu location, or detailed steps.',
            $lang
        );
    }

    private function buildSmallTalkReply(string $role, string $lang): string
    {
        return $this->messageByLang(
            'Siap. Saya lanjut bantu sesuai role Anda ('.$this->roleScopeLabel($role, $lang).'). Silakan kirim pertanyaan tentang menu atau alur aplikasi Absensi PKL.',
            'Sure. I will assist based on your role scope ('.$this->roleScopeLabel($role, $lang).'). Ask any question about Absensi PKL menus or workflows.',
            $lang
        );
    }

    /**
     * @param array<int, array{key:string,name:string,url:string}> $menus
     */
    private function buildCapabilityReply(string $role, array $menus, string $lang): string
    {
        return $this->messageByLang(
            'Saya bisa bantu kamu memahami dan menggunakan aplikasi Absensi PKL, seperti menjelaskan fungsi menu, memberi panduan langkah penggunaan fitur, menjelaskan arti status data, mengarahkan ke halaman yang tepat, dan membantu saat ada kendala penggunaan.',
            'I can help you understand and use the Absensi PKL application by explaining menu functions, giving step-by-step guidance, explaining status meanings, directing you to the right page, and helping with usage issues.',
            $lang
        );
    }

    private function buildRoleDutyReply(string $role, string $lang): string
    {
        $id = match ($role) {
            'siswa' => "Role Anda: Siswa\nBoleh: check-in/check-out, isi daily report, pengajuan izin/sakit, lihat data diri sendiri.\nTidak boleh: akses data siswa lain, data guru, akses admin, laporan global.",
            'pembimbing_pkl' => "Role Anda: Pembimbing Sekolah\nBoleh: validasi absensi dan validasi pengajuan izin/sakit siswa bimbingan sekolah.\nTidak boleh: akses data sekolah global di luar scope.",
            'instruktur' => "Role Anda: Pembimbing\nBoleh: memberi catatan mingguan dan monitoring progres jurusan.\nTidak boleh: akses data di luar jurusan.",
            'kajur' => "Role Anda: Kajur\nBoleh: meninjau ringkasan mingguan dan memberi catatan mingguan jurusan.\nTidak boleh: akses jurusan lain.",
            'wali_kelas' => "Role Anda: Wali Kelas\nBoleh: lihat siswa di kelasnya, monitoring harian.\nTidak boleh: akses kelas lain.",
            'kesiswaan' => "Role Anda: Kesiswaan\nBoleh: melihat data siswa (sesuai kebijakan), rekap dan statistik.\nTidak boleh: mengubah data atau validasi harian.",
            'kepsek' => "Role Anda: Kepala Sekolah\nBoleh: melihat data global dan dashboard eksekutif.\nTidak boleh: input atau validasi harian.",
            'admin_sekolah' => "Role Anda: Admin Sekolah\nBoleh: kelola data operasional dan konfigurasi sesuai akses yang diberikan.\nBatas akses mengikuti pengaturan superadmin.",
            'superadmin' => "Role Anda: Superadmin\nBoleh: mengelola konfigurasi sistem dan hak akses menu seluruh role.",
            default => 'Role Anda mengikuti akun aktif. Saya bisa jelaskan menu yang Anda akses saat ini.',
        };

        $en = match ($role) {
            'siswa' => 'Your tasks (Student): check-in/check-out, fill daily report, submit leave/sick request if needed, and review validation notes.',
            'pembimbing_pkl' => 'Your tasks (School Mentor): validate attendance and requests for assigned students.',
            'instruktur' => 'Your tasks (Field Mentor): write weekly notes and monitor department progress.',
            'kajur' => 'Your tasks (Department Head): review weekly department summary and write weekly notes.',
            'wali_kelas' => 'Your tasks (Homeroom Teacher): monitor recap and analysis for your class.',
            'kesiswaan' => 'Your tasks (Student Affairs): monitor student data at student-affairs scope through available report menus.',
            'kepsek' => 'Your tasks (Principal): monitor school-level summaries and analyses for decision making.',
            'admin_sekolah' => 'Your tasks (School Admin): manage operational data (users, locations, settings, reports) based on granted access.',
            'superadmin' => 'Your tasks (Superadmin): manage full system configuration and permissions for all roles.',
            default => 'Your tasks follow the currently active account role. I can explain details per accessible menu.',
        };

        return $this->messageByLang($id, $en, $lang);
    }

    private function buildAccessDeniedReply(string $role, string $lang): string
    {
        $allowed = match ($role) {
            'siswa' => 'Anda bisa tanya check-in/check-out, daily report, pengajuan izin/sakit, dan status data milik Anda sendiri.',
            'pembimbing_pkl' => 'Anda bisa tanya validasi absensi, validasi pengajuan, dan siswa bimbingan sekolah Anda.',
            'instruktur' => 'Anda bisa tanya catatan mingguan dan monitoring progres jurusan.',
            'kajur' => 'Anda bisa tanya rekap jurusan, validasi mingguan, dan analisis jurusan.',
            'wali_kelas' => 'Anda bisa tanya monitoring siswa di kelas Anda.',
            'kesiswaan' => 'Anda bisa tanya rekap dan statistik siswa sesuai menu yang tersedia.',
            'kepsek' => 'Anda bisa tanya ringkasan dashboard eksekutif dan laporan global yang tersedia.',
            default => 'Anda bisa tanya menu yang tersedia pada akun Anda saat ini.',
        };

        return $this->messageByLang(
            'Maaf, Anda tidak memiliki akses untuk melihat data tersebut. '.$allowed,
            'Sorry, you do not have access to that data. Please ask about menus and data available to your current role.',
            $lang
        );
    }

    private function buildOutOfScopeReply(string $lang): string
    {
        return $this->messageByLang(
            'Maaf, saya hanya dapat membantu terkait penggunaan aplikasi Absensi PKL.',
            'Sorry, I can only help with Absensi PKL application usage.',
            $lang
        );
    }

    private function roleScopeLabel(string $role, string $lang): string
    {
        $id = match ($role) {
            'siswa' => 'Siswa',
            'pembimbing_pkl' => 'Instruktur PKL',
            'instruktur' => 'Pembimbing',
            'kajur' => 'Kajur',
            'wali_kelas' => 'Wali Kelas',
            'kesiswaan' => 'Kesiswaan',
            'kepsek' => 'Kepala Sekolah',
            'admin_sekolah' => 'Admin Sekolah',
            'superadmin' => 'Superadmin',
            default => 'Role Aktif',
        };

        $en = match ($role) {
            'siswa' => 'Student',
            'pembimbing_pkl' => 'Internship Instructor',
            'instruktur' => 'School Mentor',
            'kajur' => 'Department Head',
            'wali_kelas' => 'Homeroom Teacher',
            'kesiswaan' => 'Student Affairs',
            'kepsek' => 'Principal',
            'admin_sekolah' => 'School Admin',
            'superadmin' => 'Superadmin',
            default => 'Active Role',
        };

        return $this->messageByLang($id, $en, $lang);
    }
}

