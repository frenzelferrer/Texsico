-- Texsico chat reply + live polling patch (safe re-run version)
-- Works even if some indexes already exist from older optimization patches.

SET @db := DATABASE();

-- 1) reply_to_message_id column
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @db
              AND TABLE_NAME = 'messages'
              AND COLUMN_NAME = 'reply_to_message_id'
        ),
        'SELECT "reply_to_message_id already exists" AS info',
        'ALTER TABLE messages ADD COLUMN reply_to_message_id INT NULL AFTER media_duration'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) sender/receiver/id index for one direction
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = @db
              AND TABLE_NAME = 'messages'
              AND INDEX_NAME = 'idx_messages_pair_sender'
        ),
        'SELECT "idx_messages_pair_sender already exists" AS info',
        'ALTER TABLE messages ADD INDEX idx_messages_pair_sender (sender_id, receiver_id, id)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3) sender/receiver/id index for reverse direction
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = @db
              AND TABLE_NAME = 'messages'
              AND INDEX_NAME = 'idx_messages_pair_receiver'
        ),
        'SELECT "idx_messages_pair_receiver already exists" AS info',
        'ALTER TABLE messages ADD INDEX idx_messages_pair_receiver (receiver_id, sender_id, id)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4) receiver/read index (skip if it already exists from optimization_patch.sql)
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = @db
              AND TABLE_NAME = 'messages'
              AND INDEX_NAME = 'idx_messages_receiver_read'
        ),
        'SELECT "idx_messages_receiver_read already exists" AS info',
        'ALTER TABLE messages ADD INDEX idx_messages_receiver_read (receiver_id, is_read, id)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5) reply_to_message_id index
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = @db
              AND TABLE_NAME = 'messages'
              AND INDEX_NAME = 'idx_messages_reply_to'
        ),
        'SELECT "idx_messages_reply_to already exists" AS info',
        'ALTER TABLE messages ADD INDEX idx_messages_reply_to (reply_to_message_id)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6) self-reference foreign key
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = @db
              AND TABLE_NAME = 'messages'
              AND CONSTRAINT_NAME = 'fk_messages_reply_to_message'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ),
        'SELECT "fk_messages_reply_to_message already exists" AS info',
        'ALTER TABLE messages ADD CONSTRAINT fk_messages_reply_to_message FOREIGN KEY (reply_to_message_id) REFERENCES messages(id) ON DELETE SET NULL'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
