-- =============================================================================
-- HUDDERSHUB COLLECTION SYSTEM SETUP
-- Run this in your Oracle SQL Workshop or SQL Plus
-- =============================================================================

-- 1. DROP TABLES (IN ORDER OF DEPENDENCY)
BEGIN EXECUTE IMMEDIATE 'DROP TABLE COLLECTION_QUEUE CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TABLE COLLECTION_SESSION CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/

-- 2. CREATE TABLES
CREATE TABLE COLLECTION_SESSION (
    session_id  NUMBER PRIMARY KEY,
    slot_id     NUMBER NOT NULL REFERENCES COLLECTION_SLOT(slot_id),
    started_at  DATE DEFAULT SYSDATE,
    ended_at    DATE,
    status      VARCHAR2(10) DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','ENDED')),
    started_by  NUMBER NOT NULL REFERENCES HUDDER_USER(user_id)
);

CREATE TABLE COLLECTION_QUEUE (
    queue_id        NUMBER PRIMARY KEY,
    session_id      NUMBER NOT NULL REFERENCES COLLECTION_SESSION(session_id),
    order_id        NUMBER NOT NULL REFERENCES HUDDER_ORDER(order_id),
    queue_position  NUMBER NOT NULL,
    status          VARCHAR2(15) DEFAULT 'Waiting' CHECK (status IN ('Waiting','Called','Collected','Missed')),
    called_at       DATE,
    collected_at    DATE
);

-- 3. SEQUENCES & TRIGGERS
CREATE SEQUENCE collection_session_seq START WITH 1 INCREMENT BY 1;
/
CREATE OR REPLACE TRIGGER collection_session_bir
BEFORE INSERT ON COLLECTION_SESSION
FOR EACH ROW
BEGIN
  IF :NEW.session_id IS NULL THEN
    :NEW.session_id := collection_session_seq.NEXTVAL;
  END IF;
END;
/

CREATE SEQUENCE collection_queue_seq START WITH 1 INCREMENT BY 1;
/
CREATE OR REPLACE TRIGGER collection_queue_bir
BEFORE INSERT ON COLLECTION_QUEUE
FOR EACH ROW
BEGIN
  IF :NEW.queue_id IS NULL THEN
    :NEW.queue_id := collection_queue_seq.NEXTVAL;
  END IF;
END;
/

-- 4. APEX PAGE 27 BUTTON LOGIC (FOR REFERENCE)
/*
   [START COLLECTION BUTTON]
   DECLARE
       v_session_id NUMBER;
   BEGIN
       INSERT INTO COLLECTION_SESSION (slot_id, started_by, status)
       VALUES (:P27_SLOT_ID, :APP_USER_ID, 'ACTIVE')
       RETURNING session_id INTO v_session_id;

       INSERT INTO COLLECTION_QUEUE (session_id, order_id, queue_position, status)
       SELECT v_session_id, order_id, ROWNUM, 'Waiting'
       FROM HUDDER_ORDER
       WHERE slot_id = :P27_SLOT_ID
       AND status = 'Ready'
       ORDER BY order_id ASC;

       :P27_SESSION_ID := v_session_id;
   END;

   [CALL NEXT BUTTON]
   DECLARE
       v_queue_id NUMBER;
   BEGIN
       SELECT queue_id INTO v_queue_id
       FROM COLLECTION_QUEUE
       WHERE session_id = :P27_SESSION_ID AND status = 'Waiting'
       ORDER BY queue_position ASC
       FETCH FIRST 1 ROW ONLY;

       UPDATE COLLECTION_QUEUE
       SET status = 'Called', called_at = SYSDATE
       WHERE queue_id = v_queue_id;
   END;

   [MARK COLLECTED BUTTON]
   DECLARE
       v_order_id NUMBER;
   BEGIN
       SELECT order_id INTO v_order_id
       FROM COLLECTION_QUEUE
       WHERE session_id = :P27_SESSION_ID AND status = 'Called'
       FETCH FIRST 1 ROW ONLY;

       UPDATE COLLECTION_QUEUE
       SET status = 'Collected', collected_at = SYSDATE
       WHERE session_id = :P27_SESSION_ID AND status = 'Called';

       UPDATE HUDDER_ORDER
       SET status = 'Collected', status_updated_at = SYSDATE
       WHERE order_id = v_order_id;
   END;
*/

-- 5. ORDS REST API DEFINITION (FOR BRIDGE)
-- URI Template: now-serving
-- Method: GET
-- Source:
/*
SELECT q.order_id
FROM COLLECTION_QUEUE q
JOIN COLLECTION_SESSION s ON s.session_id = q.session_id
WHERE s.status = 'ACTIVE'
AND q.status = 'Called'
ORDER BY q.called_at DESC
FETCH FIRST 1 ROW ONLY;
*/

COMMIT;
