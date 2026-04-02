
ALTER TABLE users
    ADD INDEX idx_users_full_name (full_name);

ALTER TABLE posts
    ADD INDEX idx_posts_user_created (user_id, created_at, id);

ALTER TABLE comments
    ADD INDEX idx_comments_post_created (post_id, created_at, id),
    ADD INDEX idx_comments_user_created (user_id, created_at, id);

ALTER TABLE likes
    ADD INDEX idx_likes_user_post (user_id, post_id);

ALTER TABLE messages
    ADD INDEX idx_messages_sender_receiver_id (sender_id, receiver_id, id),
    ADD INDEX idx_messages_receiver_sender_read (receiver_id, sender_id, is_read, id),
    ADD INDEX idx_messages_receiver_read (receiver_id, is_read, id),
    ADD INDEX idx_messages_created (created_at, id);
