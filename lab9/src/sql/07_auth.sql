ALTER TABLE users
ADD COLUMN password_hash VARCHAR(255),
ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'
CHECK (role IN ('admin', 'user'));

UPDATE users
SET password_hash = '$2y$12$sPoCQBGLkvqvlwaqJFudyeF2S4KntX/36HTQ4c8uCTFXz.UR3dGWK';

ALTER TABLE users
ALTER COLUMN password_hash SET NOT NULL;

UPDATE users
SET role = 'admin'
WHERE id = 1;

CREATE TABLE auth_tokens (
    token_hash CHAR(64) PRIMARY KEY,
    user_id INTEGER NOT NULL
        REFERENCES users(id) ON DELETE CASCADE,
    expires_at TIMESTAMPTZ NOT NULL
);