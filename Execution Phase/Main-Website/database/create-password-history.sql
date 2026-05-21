-- Password History Table
-- Stores previous passwords to prevent reuse
-- Run this once in Oracle SQL*Plus or SQL Developer

CREATE TABLE PASSWORD_HISTORY (
    history_id      NUMBER PRIMARY KEY,
    user_id         NUMBER NOT NULL,
    old_password    VARCHAR2(100) NOT NULL,
    changed_at      DATE DEFAULT SYSDATE,
    CONSTRAINT fk_password_history_user FOREIGN KEY (user_id) REFERENCES HUDDER_USER(user_id) ON DELETE CASCADE
);

CREATE SEQUENCE seq_password_history START WITH 1 INCREMENT BY 1 NOCACHE;

CREATE OR REPLACE TRIGGER trg_password_history
BEFORE INSERT ON PASSWORD_HISTORY
FOR EACH ROW
BEGIN
    :NEW.history_id := seq_password_history.NEXTVAL;
END;
/
