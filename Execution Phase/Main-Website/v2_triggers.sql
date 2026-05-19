--Sequence for user
DROP SEQUENCE seq_Hudder_user;

CREATE SEQUENCE seq_Hudder_user
START WITH 9
INCREMENT BY 1;

--Sequence for shop
DROP SEQUENCE seq_Shop;

CREATE SEQUENCE seq_Shop
START WITH 5
INCREMENT BY 1;

--Sequence for product
DROP SEQUENCE seq_Product;

CREATE SEQUENCE seq_Product
START WITH 32
INCREMENT BY 1;

--Sequence for customer
DROP SEQUENCE seq_Customer;

CREATE SEQUENCE seq_Customer
START WITH 4
INCREMENT BY 1;

--Sequence for trader
DROP SEQUENCE seq_Trader;

CREATE SEQUENCE seq_Trader
START WITH 5
INCREMENT BY 1;

--Sequence for product_category
DROP SEQUENCE seq_Product_category;

CREATE SEQUENCE seq_Product_category
START WITH 6
INCREMENT BY 1;

DROP SEQUENCE seq_Order;

CREATE SEQUENCE seq_Order 
START WITH 31 
INCREMENT BY 1;

DROP SEQUENCE seq_Review;

CREATE SEQUENCE seq_Review 
START WITH 5 
INCREMENT BY 1;

DROP SEQUENCE seq_Discount;

CREATE SEQUENCE seq_Discount 
START WITH 5 
INCREMENT BY 1;

DROP SEQUENCE seq_Favourite;

CREATE SEQUENCE seq_Favourite 
START WITH 5 
INCREMENT BY 1;

--trigger to automate the sequence and auto increase id
CREATE OR REPLACE TRIGGER trg_Hudder_user
BEFORE INSERT ON HUDDER_USER
FOR EACH ROW
BEGIN
    IF :NEW.user_id IS NULL THEN
        SELECT seq_Hudder_user.NEXTVAL
        INTO :NEW.user_id
        FROM DUAL;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_Shop
BEFORE INSERT ON SHOP
FOR EACH ROW
BEGIN
    IF :NEW.shop_id IS NULL THEN
        SELECT seq_Shop.NEXTVAL
        INTO :NEW.shop_id
        FROM DUAL;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_Product
BEFORE INSERT ON PRODUCT
FOR EACH ROW
BEGIN
    IF :NEW.product_id IS NULL THEN
        SELECT seq_Product.NEXTVAL
        INTO :NEW.product_id
        FROM DUAL;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_Customer
BEFORE INSERT ON CUSTOMER
FOR EACH ROW
BEGIN
    IF :NEW.customer_id IS NULL THEN
        SELECT seq_Customer.NEXTVAL
        INTO :NEW.customer_id
        FROM DUAL;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_Trader
BEFORE INSERT ON TRADER
FOR EACH ROW
BEGIN
    IF :NEW.trader_id IS NULL THEN
        SELECT seq_Trader.NEXTVAL
        INTO :NEW.trader_id
        FROM DUAL;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_Product_category
BEFORE INSERT ON PRODUCT_CATEGORY
FOR EACH ROW
BEGIN
    IF :NEW.category_id IS NULL THEN
        SELECT seq_Product_category.NEXTVAL
        INTO :NEW.category_id
        FROM DUAL;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_Order
BEFORE INSERT ON HUDDER_ORDER
FOR EACH ROW
BEGIN
    IF :NEW.order_id IS NULL THEN
        SELECT seq_Order.NEXTVAL 
        INTO :NEW.order_id 
        FROM DUAL;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_Review
BEFORE INSERT ON REVIEW
FOR EACH ROW
BEGIN
    IF :NEW.review_id IS NULL THEN
        SELECT seq_Review.NEXTVAL 
        INTO :NEW.review_id 
        FROM DUAL;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_Discount
BEFORE INSERT ON DISCOUNT
FOR EACH ROW
BEGIN
    IF :NEW.discount_id IS NULL THEN
        SELECT seq_Discount.NEXTVAL 
        INTO :NEW.discount_id 
        FROM DUAL;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_Favourite
BEFORE INSERT ON FAVOURITE
FOR EACH ROW
BEGIN
    IF :NEW.favourite_id IS NULL THEN
        SELECT seq_Favourite.NEXTVAL 
        INTO :NEW.favourite_id 
        FROM DUAL;
    END IF;
END;
/


--ALTER TABLE PRODUCT ADD created_at DATE DEFAULT SYSDATE;

/*CREATE OR REPLACE TRIGGER trg_product_created_at
BEFORE INSERT ON PRODUCT
FOR EACH ROW
BEGIN
    IF :NEW.created_at IS NULL THEN
        :NEW.created_at := SYSDATE;
    END IF;
END;
/
*/

CREATE OR REPLACE TRIGGER trg_prevent_negative_stock
BEFORE UPDATE ON PRODUCT
FOR EACH ROW
BEGIN
    IF :NEW.stock < 0 THEN
        RAISE_APPLICATION_ERROR(-20011, 'Stock cannot be negative.');
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_slot_capacity
BEFORE INSERT ON HUDDER_ORDER
FOR EACH ROW
DECLARE
  v_booked  NUMBER;
  v_capacity NUMBER;
BEGIN
  SELECT COUNT(*) INTO v_booked
  FROM HUDDER_ORDER WHERE slot_id = :NEW.slot_id;

  SELECT capacity INTO v_capacity
  FROM COLLECTION_SLOT WHERE slot_id = :NEW.slot_id;

  IF v_booked >= v_capacity THEN
    RAISE_APPLICATION_ERROR(-20001, 'Slot is fully booked!');
  END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_reduce_stock
AFTER INSERT ON ORDER_PRODUCT
FOR EACH ROW
BEGIN
  UPDATE PRODUCT
  SET stock = stock - :NEW.quantity
  WHERE product_id = :NEW.product_id;
END;
/

DROP SEQUENCE collection_slot_seq;

CREATE SEQUENCE collection_slot_seq 
START WITH 100 
INCREMENT BY 1;

CREATE OR REPLACE TRIGGER trg_collection_slot
BEFORE INSERT ON COLLECTION_SLOT
FOR EACH ROW
BEGIN
    IF :NEW.slot_id IS NULL THEN
        SELECT collection_slot_seq.NEXTVAL 
        INTO :NEW.slot_id 
        FROM DUAL;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER TRG_LIMIT_TRADER_SHOPS
BEFORE INSERT ON SHOP
FOR EACH ROW
DECLARE
    v_shop_count NUMBER;
BEGIN
    -- Count how many shops this user already owns
    SELECT COUNT(*)
    INTO v_shop_count
    FROM SHOP
    WHERE user_id = :NEW.user_id;

    -- If they have 2 or more, stop the process and show a custom error
    IF v_shop_count >= 2 THEN
        RAISE_APPLICATION_ERROR(-20001, 'Business Rule Violation: This trader already owns 2 shops. A trader cannot own more than 2 shops.');
    END IF;
END;
/