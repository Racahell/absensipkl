<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Basic consistency on guidance note slots.
        DB::statement("
            ALTER TABLE student_guidance_notes
            ADD CONSTRAINT chk_guidance_distinct_mentors
            CHECK (
                mentor1_user_id IS NULL
                OR mentor2_user_id IS NULL
                OR mentor1_user_id <> mentor2_user_id
            )
        ");

        // Keep assignment table consistent with business rules at DB level.
        DB::unprepared("
            CREATE TRIGGER trg_sma_before_insert
            BEFORE INSERT ON student_mentor_assignments
            FOR EACH ROW
            BEGIN
                DECLARE mentor_count INT DEFAULT 0;
                DECLARE student_role VARCHAR(50);
                DECLARE mentor_role_user VARCHAR(50);

                SELECT role INTO student_role FROM users WHERE id = NEW.student_user_id LIMIT 1;
                IF student_role IS NULL OR student_role <> 'siswa' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'student_user_id harus user role siswa';
                END IF;

                SELECT role INTO mentor_role_user FROM users WHERE id = NEW.mentor_user_id LIMIT 1;
                IF mentor_role_user IS NULL OR mentor_role_user = 'siswa' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'mentor_user_id harus user non-siswa';
                END IF;

                IF NEW.mentor_role NOT IN ('pembimbing_pkl', 'instruktur') THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'mentor_role tidak valid';
                END IF;

                SELECT COUNT(*) INTO mentor_count
                FROM student_mentor_assignments
                WHERE student_user_id = NEW.student_user_id;

                IF mentor_count >= 2 THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Maksimal 2 mentor per siswa';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_sma_before_update
            BEFORE UPDATE ON student_mentor_assignments
            FOR EACH ROW
            BEGIN
                DECLARE mentor_count INT DEFAULT 0;
                DECLARE student_role VARCHAR(50);
                DECLARE mentor_role_user VARCHAR(50);

                SELECT role INTO student_role FROM users WHERE id = NEW.student_user_id LIMIT 1;
                IF student_role IS NULL OR student_role <> 'siswa' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'student_user_id harus user role siswa';
                END IF;

                SELECT role INTO mentor_role_user FROM users WHERE id = NEW.mentor_user_id LIMIT 1;
                IF mentor_role_user IS NULL OR mentor_role_user = 'siswa' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'mentor_user_id harus user non-siswa';
                END IF;

                IF NEW.mentor_role NOT IN ('pembimbing_pkl', 'instruktur') THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'mentor_role tidak valid';
                END IF;

                IF NEW.student_user_id <> OLD.student_user_id THEN
                    SELECT COUNT(*) INTO mentor_count
                    FROM student_mentor_assignments
                    WHERE student_user_id = NEW.student_user_id;

                    IF mentor_count >= 2 THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Maksimal 2 mentor per siswa';
                    END IF;
                END IF;
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_sma_before_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_sma_before_update');

        DB::statement('ALTER TABLE student_guidance_notes DROP CONSTRAINT chk_guidance_distinct_mentors');
    }
};

