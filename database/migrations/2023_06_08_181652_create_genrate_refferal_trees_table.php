<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGenrateRefferalTreesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("

CREATE  PROCEDURE `generate_referral_tree`(child_id INT, debug BIT, level_count INT)
proc_ref_tree:
        BEGIN

            IF IFNULL(level_count, 0) = 0
            THEN
                SET level_count = 5;
            END IF;

            SET @level := 1;
            SET @parent_id := 0;
            SET @parent_u_id := '';
            SET @child_u_id := '';

            SELECT u_id, parent_id INTO @child_u_id, @parent_u_id FROM users WHERE id = child_id;

            SELECT id INTO @parent_id FROM users WHERE u_id = @parent_u_id;

            IF @parent_id IS NULL
            THEN
                LEAVE proc_ref_tree;
            END IF;

            DROP TABLE IF EXISTS referral_tree;
            CREATE TEMPORARY TABLE referral_tree
            (
                parent_id INT,
                parent_u_id VARCHAR(40),
                child_id INT,
                child_u_id VARCHAR(40),
                level INT
            );

            INSERT INTO referral_tree (parent_id, parent_u_id, child_id, child_u_id, level)
            VALUES (@parent_id, @parent_u_id, child_id, @child_u_id, @level);

            referral_loop: WHILE true DO

                SELECT parent_id INTO @parent_u_id FROM users WHERE id = @parent_id;

                IF IFNULL(@parent_u_id, '0') = '0' OR @level >= level_count
                THEN
                    LEAVE referral_loop;
                END IF;

                SELECT id INTO @parent_id FROM users WHERE u_id = @parent_u_id;
                SET @level := @level + 1;

                INSERT INTO referral_tree (parent_id, parent_u_id, child_id, child_u_id, level)
                VALUES (@parent_id, @parent_u_id, child_id, @child_u_id, @level);

            END WHILE;

            IF debug = 1
            THEN
                SELECT * FROM referral_tree ORDER BY level DESC;
            ELSE
                INSERT INTO referrals (parent_id, parent_u_id, child_id, child_u_id, level, created_at, updated_at)
                SELECT rt.parent_id, rt.parent_u_id, rt.child_id, rt.child_u_id, rt.level, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                FROM referral_tree rt
                LEFT OUTER JOIN referrals r ON rt.parent_id = r.parent_id AND rt.child_id = r.child_id AND rt.level = r.level
                WHERE r.id IS NULL
                ORDER BY level DESC;
            END IF;
            DROP TABLE IF EXISTS referral_tree;
        END;
        ");

        //
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('genrate_refferal_trees');
    }
}
