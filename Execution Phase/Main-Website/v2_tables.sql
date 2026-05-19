-- DROP TABLES
DROP TABLE HUDDER_USER CASCADE CONSTRAINTS;
DROP TABLE SHOP CASCADE CONSTRAINTS;
DROP TABLE TRADER CASCADE CONSTRAINTS;
DROP TABLE HUDDER_ADMIN CASCADE CONSTRAINTS;
DROP TABLE CUSTOMER CASCADE CONSTRAINTS;
DROP TABLE PRODUCT CASCADE CONSTRAINTS;
DROP TABLE COLLECTION_SLOT CASCADE CONSTRAINTS;
DROP TABLE HUDDER_ORDER CASCADE CONSTRAINTS;
DROP TABLE CART CASCADE CONSTRAINTS;
DROP TABLE PAYMENT CASCADE CONSTRAINTS;
DROP TABLE CART_ITEM CASCADE CONSTRAINTS;
DROP TABLE REVIEW CASCADE CONSTRAINTS;
DROP TABLE DISCOUNT CASCADE CONSTRAINTS;
DROP TABLE FAVOURITE CASCADE CONSTRAINTS;
DROP TABLE PRODUCT_CATEGORY CASCADE CONSTRAINTS;
DROP TABLE ORDER_PRODUCT CASCADE CONSTRAINTS;


--------------------------------------------------------------------------------------- CREATE TABLES  -------------------------------------------------------------------------------------------------------------

CREATE TABLE HUDDER_USER(
    user_id       NUMBER,
    firstname     VARCHAR2(20) NOT NULL,
    lastname      VARCHAR2(20) NOT NULL,
    email         VARCHAR2(50) NOT NULL,
    user_password VARCHAR2(122) NOT NULL,
    user_role     VARCHAR2(10) NOT NULL,
    phone_number  VARCHAR2(15),
    address       VARCHAR2(100),
    date_of_birth DATE,
    gender        VARCHAR2(10),
    profile_image BLOB,
    CONSTRAINT user_pk PRIMARY KEY (user_id)
);

------------------------------------------------------------------------------------------ ROLES ----------------------------------------------------------------------------------------------------------------------
CREATE TABLE TRADER (
    trader_id NUMBER,
    user_id   NUMBER NOT NULL,
    status    VARCHAR2(20) DEFAULT 'Pending',
    CONSTRAINT trader_pk PRIMARY KEY (trader_id),
    CONSTRAINT trader_user_fk FOREIGN KEY (user_id) REFERENCES HUDDER_USER(user_id)
);

CREATE TABLE HUDDER_ADMIN (
    admin_id NUMBER,
    user_id  NUMBER NOT NULL,
    CONSTRAINT admin_pk PRIMARY KEY (admin_id),
    CONSTRAINT admin_user_fk FOREIGN KEY (user_id) REFERENCES HUDDER_USER(user_id)
);

CREATE TABLE CUSTOMER (
    customer_id NUMBER,
    user_id     NUMBER NOT NULL,
    CONSTRAINT customer_pk PRIMARY KEY (customer_id),
    CONSTRAINT customer_user_fk FOREIGN KEY (user_id) REFERENCES HUDDER_USER(user_id)
);
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

------------------ SHOP --------------------
CREATE TABLE SHOP (
    shop_id        NUMBER,
    name           VARCHAR2(50) NOT NULL,
    description    VARCHAR2(200),
    location       VARCHAR2(100),
    contact_number VARCHAR2(15),
    shop_logo BLOB,
    user_id        NUMBER NOT NULL,
    CONSTRAINT shop_pk PRIMARY KEY (shop_id),
    CONSTRAINT shop_user_fk FOREIGN KEY (user_id) REFERENCES HUDDER_USER(user_id)
);

------------------ Product Category --------------------
CREATE TABLE PRODUCT_CATEGORY (
    category_id   NUMBER,
    category_name VARCHAR2(50) NOT NULL,
    description   VARCHAR2(200),   
    CONSTRAINT category_pk PRIMARY KEY (category_id)    
);

------------------ Product --------------------
CREATE TABLE PRODUCT (
    product_id    NUMBER,
    name          VARCHAR2(50) NOT NULL,
    description   VARCHAR2(200),
    price         NUMBER(10,2) NOT NULL,
    stock         NUMBER NOT NULL,
    min_order     NUMBER,
    max_order     NUMBER,
    reorder_label NUMBER,
    allergen_info VARCHAR2(200),
    shop_id       NUMBER NOT NULL,
    category_id   NUMBER,
    product_image BLOB,
    CONSTRAINT product_pk PRIMARY KEY (product_id),
    CONSTRAINT product_shop_fk FOREIGN KEY (shop_id) REFERENCES SHOP(shop_id),
    CONSTRAINT product_category_fk FOREIGN KEY (category_id) REFERENCES PRODUCT_CATEGORY(category_id)
);  

------------------ Collection Slot --------------------
CREATE TABLE COLLECTION_SLOT (
    slot_id   NUMBER,
    slot_date DATE NOT NULL,
    slot_time VARCHAR2(20) NOT NULL,
    capacity  NUMBER NOT NULL,
    location  VARCHAR2(100),
    CONSTRAINT slot_pk PRIMARY KEY (slot_id),
    CONSTRAINT slot_capacity_chk CHECK (capacity <= 20),
    CONSTRAINT slot_time_chk CHECK (slot_time IN ('10:00-13:00', '13:00-16:00', '16:00-19:00')),
    CONSTRAINT slot_day_chk CHECK (TO_CHAR(slot_date, 'DY') IN ('WED', 'THU', 'FRI'))
);

------------------ Order --------------------
CREATE TABLE HUDDER_ORDER (
    order_id   NUMBER,
    order_date DATE NOT NULL,
    order_time VARCHAR2(10) NOT NULL,
    status     VARCHAR2(20) NOT NULL,
    user_id    NUMBER NOT NULL,
    slot_id    NUMBER,
    CONSTRAINT order_pk PRIMARY KEY (order_id),
    CONSTRAINT order_user_fk FOREIGN KEY (user_id) REFERENCES HUDDER_USER(user_id),
    CONSTRAINT order_slot_fk FOREIGN KEY (slot_id) REFERENCES COLLECTION_SLOT(slot_id),
    CONSTRAINT order_status_chk CHECK (status IN ('Pending', 'Completed', 'Cancelled', 'Delivered'))
);

------------------ Payment --------------------
CREATE TABLE PAYMENT (
    payment_id   NUMBER,
    amount       NUMBER(10,2) NOT NULL,
    method       VARCHAR2(20) NOT NULL,
    status       VARCHAR2(20) NOT NULL,
    payment_date DATE NOT NULL,
    order_id     NUMBER NOT NULL,
    user_id      NUMBER NOT NULL,
    CONSTRAINT payment_pk PRIMARY KEY (payment_id),
    CONSTRAINT payment_order_fk FOREIGN KEY (order_id) REFERENCES HUDDER_ORDER(order_id),
    CONSTRAINT payment_user_fk FOREIGN KEY (user_id) REFERENCES HUDDER_USER(user_id),
    CONSTRAINT payment_status_chk CHECK (status IN ('Pending', 'Completed', 'Failed'))
);

------------------ Cart --------------------
CREATE TABLE CART (
    cart_id    NUMBER,
    created_at DATE NOT NULL,
    user_id    NUMBER NOT NULL,
    CONSTRAINT cart_pk PRIMARY KEY (cart_id),
    CONSTRAINT cart_user_fk FOREIGN KEY (user_id) REFERENCES HUDDER_USER(user_id)
);

------------------ Cart Item --------------------
CREATE TABLE CART_ITEM (
    cart_item_id NUMBER,
    quantity     NUMBER NOT NULL,
    cart_id      NUMBER NOT NULL,
    product_id   NUMBER NOT NULL,
    CONSTRAINT cart_item_pk PRIMARY KEY (cart_item_id),
    CONSTRAINT cart_item_cart_fk FOREIGN KEY (cart_id) REFERENCES CART(cart_id),
    CONSTRAINT cart_item_product_fk FOREIGN KEY (product_id) REFERENCES PRODUCT(product_id),
    CONSTRAINT cart_item_qty_chk CHECK (quantity > 0)
);

------------------ Review --------------------
CREATE TABLE REVIEW (
    review_id   NUMBER,
    review_text VARCHAR2(500),
    --review_date DATE DEFAULT SYSDATE NOT NULL,
    rating      NUMBER(2,1) NOT NULL,
    user_id     NUMBER NOT NULL,
    product_id  NUMBER NOT NULL,
    CONSTRAINT review_pk PRIMARY KEY (review_id),
    CONSTRAINT review_user_fk FOREIGN KEY (user_id) REFERENCES HUDDER_USER(user_id),
    CONSTRAINT review_product_fk FOREIGN KEY (product_id) REFERENCES PRODUCT(product_id)
);

------------------ Discount --------------------
CREATE TABLE DISCOUNT (
    discount_id      NUMBER,
    discount_percent NUMBER NOT NULL,
    discount_type    VARCHAR2(20) NOT NULL,
    valid_until      DATE NOT NULL,
    user_id          NUMBER NOT NULL,
    product_id       NUMBER NOT NULL,
    CONSTRAINT discount_pk PRIMARY KEY (discount_id),
    CONSTRAINT discount_user_fk FOREIGN KEY (user_id) REFERENCES HUDDER_USER(user_id),
    CONSTRAINT discount_product_fk FOREIGN KEY (product_id) REFERENCES PRODUCT(product_id)
);

------------------ Favourites --------------------
CREATE TABLE FAVOURITE (
    favourite_id NUMBER,
    created_at   DATE NOT NULL,
    user_id      NUMBER NOT NULL,
    product_id   NUMBER NOT NULL,
    CONSTRAINT favourite_pk PRIMARY KEY (favourite_id),
    CONSTRAINT favourite_user_fk FOREIGN KEY (user_id) REFERENCES HUDDER_USER(user_id),
    CONSTRAINT favourite_product_fk FOREIGN KEY (product_id) REFERENCES PRODUCT(product_id),
    CONSTRAINT favourite_unique UNIQUE (user_id, product_id)
);

------------------ Order Product --------------------
CREATE TABLE ORDER_PRODUCT (
    order_id   NUMBER NOT NULL,
    product_id NUMBER NOT NULL,
    quantity   NUMBER NOT NULL,
    unit_price NUMBER(10,2) NOT NULL,
    CONSTRAINT order_product_pk PRIMARY KEY (order_id, product_id),
    CONSTRAINT op_order_fk FOREIGN KEY (order_id) REFERENCES HUDDER_ORDER(order_id),
    CONSTRAINT op_product_fk FOREIGN KEY (product_id) REFERENCES PRODUCT(product_id)
);

--alter table
ALTER TABLE PRODUCT ADD status VARCHAR2(20) DEFAULT 'Pending';

ALTER TABLE HUDDER_USER ADD (
    created_at DATE DEFAULT SYSDATE,
    verified_at DATE,
    verification_token VARCHAR2(128),
    password_reset_token VARCHAR2(128),
    password_reset_expires DATE,
    email_notifications VARCHAR2(4000)
);

UPDATE HUDDER_USER SET created_at = NVL(created_at, SYSDATE);


----------------------------------------------------------------------------- INSERT DATA ------------------------------------------------------------------------------------------------------------------------

------------------ Users --------------------
INSERT INTO HUDDER_USER VALUES (1, 'Oliver', 'Smith', 'oliver@mail.com', 'pass1234', 'trader', '0711111111', 'Cleckheaton', TO_DATE('1990-05-10','YYYY-MM-DD'), 'Male', NULL);
INSERT INTO HUDDER_USER VALUES (2, 'Amelia', 'Brown', 'amelia@mail.com', 'pass123', 'customer', '0722222222', 'Huddersfield', TO_DATE('1998-07-22','YYYY-MM-DD'), 'Female', NULL);
INSERT INTO HUDDER_USER VALUES (3, 'Anish', 'Tandukar', 'anish', 'huddershub123', 'admin', '0733333333', 'Halifax', TO_DATE('1985-01-01','YYYY-MM-DD'), 'Male', NULL);
INSERT INTO HUDDER_USER VALUES (4, 'George', 'Wilson', 'george@mail.com', 'pass1234', 'trader', '0744444444', 'Bradford', TO_DATE('1992-03-15','YYYY-MM-DD'), 'Male', NULL);
INSERT INTO HUDDER_USER VALUES (5, 'Isla', 'Taylor', 'isla@mail.com', 'pass123', 'customer', '0755555555', 'Leeds', TO_DATE('2000-06-25','YYYY-MM-DD'), 'Female', NULL);
INSERT INTO HUDDER_USER VALUES (6, 'Jack', 'Davies', 'jack@mail.com', 'pass123', 'customer', '0766666666', 'Huddersfield', TO_DATE('1997-11-30','YYYY-MM-DD'), 'Male', NULL);
INSERT INTO HUDDER_USER VALUES (7, 'Harry', 'Evans', 'harry@mail.com', 'pass1234', 'trader', '0777777777', 'Halifax', TO_DATE('1991-02-12','YYYY-MM-DD'), 'Male', NULL);
INSERT INTO HUDDER_USER VALUES (8, 'Emily', 'Clark', 'emily@mail.com', 'pass1234', 'trader', '0788888888', 'Leeds', TO_DATE('1994-09-05','YYYY-MM-DD'), 'Female', NULL);

------------------------------------------------------------------------------           ROLES           ---------------------------------------------------------------------------------------------------------

-- Traders
INSERT INTO TRADER VALUES (1,1,'Active');
INSERT INTO TRADER VALUES (2,4,'Active');
INSERT INTO TRADER VALUES (3,7,'Pending');
INSERT INTO TRADER VALUES (4,8,'Pending');

-- Admin (ONLY ONE)
INSERT INTO HUDDER_ADMIN VALUES (1,3);

-- Customers
INSERT INTO CUSTOMER VALUES (1,2);
INSERT INTO CUSTOMER VALUES (2,5);
INSERT INTO CUSTOMER VALUES (3,6);

--SHOP
INSERT INTO SHOP VALUES (1, 'Yorkshire Deli', 'Cheese and gourmet food', 'Cleckheaton', '0711111111', NULL, 1);
INSERT INTO SHOP VALUES (2, 'Huddersfield Meats', 'Fresh butcher shop', 'Huddersfield', '0744444444', NULL, 4);
INSERT INTO SHOP VALUES (3, 'Halifax Greens', 'Organic vegetables', 'Halifax', '0777777777', NULL, 7);
INSERT INTO SHOP VALUES (4, 'Leeds Seafood', 'Fresh fish market', 'Leeds', '0788888888', NULL, 8);
INSERT INTO SHOP VALUES (5, 'Bradford Bakery', 'Bread and pastries', 'Bradford', '0711111122', NULL, 1);

--PRODUCT CATEGORY
INSERT INTO PRODUCT_CATEGORY VALUES (1, 'Delicatessen', 'Cheese and cold cuts');
INSERT INTO PRODUCT_CATEGORY VALUES (2, 'Butcher', 'Fresh meat');
INSERT INTO PRODUCT_CATEGORY VALUES (3, 'Greengrocer', 'Fruits and vegetables');
INSERT INTO PRODUCT_CATEGORY VALUES (4, 'Fishmonger', 'Seafood');
INSERT INTO PRODUCT_CATEGORY VALUES (5, 'Bakery', 'Bread and pastries');

--PRODUCT
INSERT INTO PRODUCT VALUES (2,'Beef Steak','Fresh beef cut',20.00,50,1,5,3,'None',2,2, NULL, 'Active');
INSERT INTO PRODUCT VALUES (3,'Carrots','Organic carrots',2.00,150,5,20,10,'None',3,3, NULL, 'Active');
INSERT INTO PRODUCT VALUES (4,'Salmon','Fresh salmon fish',15.00,60,1,5,4,'Fish',4,4, NULL, 'Active');
INSERT INTO PRODUCT VALUES (5,'Bread Loaf','Fresh baked bread',3.00,80,1,10,5,'Gluten',5,5, NULL, 'Active');

--COLLECTION SLOT
INSERT INTO COLLECTION_SLOT VALUES (1, TO_DATE('2025-04-02','YYYY-MM-DD'),'10:00-13:00',20,'Cleckheaton');
INSERT INTO COLLECTION_SLOT VALUES (2, TO_DATE('2025-04-03','YYYY-MM-DD'),'13:00-16:00',20,'Huddersfield');
INSERT INTO COLLECTION_SLOT VALUES (3, TO_DATE('2025-04-04','YYYY-MM-DD'),'16:00-19:00',20,'Halifax');
INSERT INTO COLLECTION_SLOT VALUES (4, TO_DATE('2025-04-09','YYYY-MM-DD'),'10:00-13:00',20,'Bradford');
INSERT INTO COLLECTION_SLOT VALUES (5, TO_DATE('2025-04-10','YYYY-MM-DD'),'13:00-16:00',20,'Leeds');

--ORDER
INSERT INTO HUDDER_ORDER VALUES (1, SYSDATE, '10:00 AM','Pending',2,1);
INSERT INTO HUDDER_ORDER VALUES (2, SYSDATE, '11:00 AM','Completed',5,2);
INSERT INTO HUDDER_ORDER VALUES (3, SYSDATE, '12:00 PM','Pending',6,3);
INSERT INTO HUDDER_ORDER VALUES (4, SYSDATE, '01:00 PM','Cancelled',2,4);
INSERT INTO HUDDER_ORDER VALUES (5, SYSDATE, '02:00 PM','Completed',5,5);

--PAYMENT
INSERT INTO PAYMENT VALUES (1,20,'PayPal','Completed',SYSDATE,1,2);
INSERT INTO PAYMENT VALUES (2,15,'PayPal','Completed',SYSDATE,2,5);
INSERT INTO PAYMENT VALUES (3,10,'PayPal','Failed',SYSDATE,3,6);
INSERT INTO PAYMENT VALUES (4,8,'PayPal','Completed',SYSDATE,4,2);
INSERT INTO PAYMENT VALUES (5,25,'PayPal','Completed',SYSDATE,5,5);

--CART
INSERT INTO CART VALUES (1,SYSDATE,2);
INSERT INTO CART VALUES (2,SYSDATE,5);
INSERT INTO CART VALUES (3,SYSDATE,6);
INSERT INTO CART VALUES (4,SYSDATE,2);
INSERT INTO CART VALUES (5,SYSDATE,5);

--CART ITEMS
INSERT INTO CART_ITEM VALUES (2,1,2,2);
INSERT INTO CART_ITEM VALUES (3,3,3,3);
INSERT INTO CART_ITEM VALUES (4,1,4,4);
INSERT INTO CART_ITEM VALUES (5,2,5,5);

--REVIEW
INSERT INTO REVIEW VALUES (2,'Nice meat quality',4.0,5,2);
INSERT INTO REVIEW VALUES (3,'Very fresh veggies',5.0,6,3);
INSERT INTO REVIEW VALUES (4,'Good fish',4.2,2,4);
INSERT INTO REVIEW VALUES (5,'Tasty bread',4.8,5,5);

--DISCOUNT
INSERT INTO DISCOUNT VALUES (2,15,'Festival',SYSDATE+20,4,2);
INSERT INTO DISCOUNT VALUES (3,5,'Clearance',SYSDATE+15,7,3);
INSERT INTO DISCOUNT VALUES (4,20,'Seasonal',SYSDATE+40,8,4);
INSERT INTO DISCOUNT VALUES (5,25,'Special',SYSDATE+25,1,5);

--ORDER_PRODUCT
INSERT INTO ORDER_PRODUCT (order_id, product_id, quantity, unit_price) VALUES (1, 5, 1, 3.00);  
INSERT INTO ORDER_PRODUCT (order_id, product_id, quantity, unit_price) VALUES (2, 2, 1, 20.00); 
INSERT INTO ORDER_PRODUCT (order_id, product_id, quantity, unit_price) VALUES (3, 3, 5, 2.00); 
INSERT INTO ORDER_PRODUCT (order_id, product_id, quantity, unit_price) VALUES (4, 4, 1, 15.00); 
INSERT INTO ORDER_PRODUCT (order_id, product_id, quantity, unit_price) VALUES (5, 3, 2, 2.00);  

-----------------------------------------------------------------------------------------------    Updating order date   ---------------------------------------------------------------------------------------------------
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-04-12', 'YYYY-MM-DD') WHERE order_id = 1;
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-04-13', 'YYYY-MM-DD') WHERE order_id = 2;
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-04-14', 'YYYY-MM-DD') WHERE order_id = 3;
----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

-- Slots 
INSERT INTO COLLECTION_SLOT VALUES (6, TO_DATE('2026-04-17','YYYY-MM-DD'), '10:00-13:00', 20, 'Huddersfield');
INSERT INTO COLLECTION_SLOT VALUES (7, TO_DATE('2026-04-17','YYYY-MM-DD'), '13:00-16:00', 20, 'Cleckheaton');
INSERT INTO COLLECTION_SLOT VALUES (8, TO_DATE('2026-04-17','YYYY-MM-DD'), '16:00-19:00', 20, 'Halifax');

-- Orders (SYSDATE = today, slot_id references the slots above)
INSERT INTO HUDDER_ORDER VALUES (6,  SYSDATE, '09:00 AM', 'Pending',   2, 6);
INSERT INTO HUDDER_ORDER VALUES (7,  SYSDATE, '09:30 AM', 'Pending',   5, 6);
INSERT INTO HUDDER_ORDER VALUES (8,  SYSDATE, '10:00 AM', 'Completed', 6, 7);
INSERT INTO HUDDER_ORDER VALUES (9,  SYSDATE, '11:00 AM', 'Pending',   2, 7);
INSERT INTO HUDDER_ORDER VALUES (10, SYSDATE, '01:00 PM', 'Pending',   5, 8);

-- Order products
INSERT INTO ORDER_PRODUCT VALUES (6,  5, 1, 3.00);
INSERT INTO ORDER_PRODUCT VALUES (7,  2, 1, 20.00);
INSERT INTO ORDER_PRODUCT VALUES (7,  3, 3, 2.00);
INSERT INTO ORDER_PRODUCT VALUES (8,  4, 2, 15.00);
INSERT INTO ORDER_PRODUCT VALUES (9,  5, 2, 3.00);
INSERT INTO ORDER_PRODUCT VALUES (9,  2, 1, 20.00);
INSERT INTO ORDER_PRODUCT VALUES (10, 3, 4, 2.00);
INSERT INTO ORDER_PRODUCT VALUES (10, 4, 1, 15.00);

-- Payments
INSERT INTO PAYMENT VALUES (6,  20.00, 'PayPal', 'Completed', SYSDATE, 6,  2);
INSERT INTO PAYMENT VALUES (7,  46.00, 'PayPal', 'Completed', SYSDATE, 7,  5);
INSERT INTO PAYMENT VALUES (8,  38.50, 'PayPal', 'Completed', SYSDATE, 8,  6);
INSERT INTO PAYMENT VALUES (9,  26.00, 'PayPal', 'Pending',   SYSDATE, 9,  2);
INSERT INTO PAYMENT VALUES (10, 23.00, 'PayPal', 'Completed', SYSDATE, 10, 5);


/*  ||||||||||||||||||||||||    NEW CHANGES    |||||||||||||||||||||||||||||||*/

--New added Costumers
INSERT INTO HUDDER_USER VALUES (9,  'Sophie',  'Johnson',  'sophie@mail.com',   'pass123', 'customer', '0799999999', 'Cleckheaton',  TO_DATE('1995-03-18','YYYY-MM-DD'), 'Female', NULL);
INSERT INTO HUDDER_USER VALUES (10, 'Liam',    'White',    'liam@mail.com',     'pass123', 'customer', '0711223344', 'Huddersfield', TO_DATE('1993-08-11','YYYY-MM-DD'), 'Male', NULL);
INSERT INTO HUDDER_USER VALUES (11, 'Chloe',   'Martin',   'chloe@mail.com',    'pass123', 'customer', '0722334455', 'Halifax',      TO_DATE('2001-12-05','YYYY-MM-DD'), 'Female', NULL);
INSERT INTO HUDDER_USER VALUES (12, 'Noah',    'Thompson', 'noah@mail.com',     'pass123', 'customer', '0733445566', 'Cleckheaton',  TO_DATE('1999-04-20','YYYY-MM-DD'), 'Male', NULL);
INSERT INTO HUDDER_USER VALUES (13, 'Grace',   'Roberts',  'grace@mail.com',    'pass123', 'customer', '0744556677', 'Huddersfield', TO_DATE('1997-07-14','YYYY-MM-DD'), 'Female', NULL);
INSERT INTO HUDDER_USER VALUES (14, 'Ethan',   'Lewis',    'ethan@mail.com',    'pass123', 'customer', '0755667788', 'Halifax',      TO_DATE('1996-01-09','YYYY-MM-DD'), 'Male', NULL);
INSERT INTO HUDDER_USER VALUES (15, 'Mia',     'Walker',   'mia@mail.com',      'pass123', 'customer', '0766778899', 'Cleckheaton',  TO_DATE('2002-05-30','YYYY-MM-DD'), 'Female', NULL);
INSERT INTO HUDDER_USER VALUES (16, 'Alfie',   'Hall',     'alfie@mail.com',    'pass123', 'customer', '0777889900', 'Huddersfield', TO_DATE('1990-11-22','YYYY-MM-DD'), 'Male', NULL);
INSERT INTO HUDDER_USER VALUES (17, 'Ella',    'Young',    'ella@mail.com',     'pass123', 'customer', '0788990011', 'Halifax',      TO_DATE('1998-09-17','YYYY-MM-DD'), 'Female', NULL);
INSERT INTO HUDDER_USER VALUES (18, 'Charlie', 'Harris',   'charlie@mail.com',  'pass123', 'customer', '0799001122', 'Huddersfield', TO_DATE('2000-02-28','YYYY-MM-DD'), 'Male', NULL);

--Assignining new Costumers
INSERT INTO CUSTOMER VALUES (4,  9);
INSERT INTO CUSTOMER VALUES (5,  10);
INSERT INTO CUSTOMER VALUES (6,  11);
INSERT INTO CUSTOMER VALUES (7,  12);
INSERT INTO CUSTOMER VALUES (8,  13);
INSERT INTO CUSTOMER VALUES (9,  14);
INSERT INTO CUSTOMER VALUES (10, 15);
INSERT INTO CUSTOMER VALUES (11, 16);
INSERT INTO CUSTOMER VALUES (12, 17);
INSERT INTO CUSTOMER VALUES (13, 18);


-- NEW ORDERS
INSERT INTO HUDDER_ORDER VALUES (11, SYSDATE, '09:00 AM', 'Pending',   9,  1);
INSERT INTO HUDDER_ORDER VALUES (12, SYSDATE, '10:00 AM', 'Completed', 10, 2);
INSERT INTO HUDDER_ORDER VALUES (13, SYSDATE, '11:00 AM', 'Pending',   11, 3);
INSERT INTO HUDDER_ORDER VALUES (14, SYSDATE, '12:00 PM', 'Cancelled', 12, 4);
INSERT INTO HUDDER_ORDER VALUES (15, SYSDATE, '01:00 PM', 'Completed', 13, 5);
INSERT INTO HUDDER_ORDER VALUES (16, SYSDATE, '02:00 PM', 'Pending',   14, 6);
INSERT INTO HUDDER_ORDER VALUES (17, SYSDATE, '03:00 PM', 'Completed', 15, 7);
INSERT INTO HUDDER_ORDER VALUES (18, SYSDATE, '09:30 AM', 'Pending',   16, 8);
INSERT INTO HUDDER_ORDER VALUES (19, SYSDATE, '10:30 AM', 'Pending',   17, 6);
INSERT INTO HUDDER_ORDER VALUES (20, SYSDATE, '11:30 AM', 'Completed', 18, 7);

-- NEW CARTS
INSERT INTO CART VALUES (6,  SYSDATE, 9);
INSERT INTO CART VALUES (7,  SYSDATE, 10);
INSERT INTO CART VALUES (8,  SYSDATE, 11);
INSERT INTO CART VALUES (9,  SYSDATE, 12);
INSERT INTO CART VALUES (10, SYSDATE, 13);
INSERT INTO CART VALUES (11, SYSDATE, 14);
INSERT INTO CART VALUES (12, SYSDATE, 15);
INSERT INTO CART VALUES (13, SYSDATE, 16);
INSERT INTO CART VALUES (14, SYSDATE, 17);
INSERT INTO CART VALUES (15, SYSDATE, 18);

-- NEW CART ITEMS
INSERT INTO CART_ITEM VALUES (12, 1, 12, 2);
INSERT INTO CART_ITEM VALUES (13, 3, 13, 3);
INSERT INTO CART_ITEM VALUES (14, 2, 14, 4);
INSERT INTO CART_ITEM VALUES (15, 1, 15, 5);

-- NEW REVIEWS
INSERT INTO REVIEW VALUES (12, 'Beef was tender and fresh',    4.2, 15, 2);
INSERT INTO REVIEW VALUES (13, 'Carrots were very crunchy',    4.9, 16, 3);
INSERT INTO REVIEW VALUES (14, 'Salmon was perfectly fresh',   4.4, 17, 4);
INSERT INTO REVIEW VALUES (15, 'Best bread in Huddersfield!',  5.0, 18, 5);

-- NEW ORDER PRODUCTS
INSERT INTO ORDER_PRODUCT VALUES (13, 2,  1, 20.00);
INSERT INTO ORDER_PRODUCT VALUES (14, 3,  4, 2.00);
INSERT INTO ORDER_PRODUCT VALUES (15, 4,  2, 15.00);
INSERT INTO ORDER_PRODUCT VALUES (15, 5,  1, 3.00);
INSERT INTO ORDER_PRODUCT VALUES (19, 2,  1, 20.00);
INSERT INTO ORDER_PRODUCT VALUES (19, 4,  1, 15.00);
INSERT INTO ORDER_PRODUCT VALUES (20, 3,  3, 2.00);
INSERT INTO ORDER_PRODUCT VALUES (20, 5,  2, 3.00);

-- NEW PAYMENTS
INSERT INTO PAYMENT VALUES (11, 17.00, 'PayPal', 'Completed', SYSDATE, 11, 9);
INSERT INTO PAYMENT VALUES (12, 16.00, 'PayPal', 'Completed', SYSDATE, 12, 10);
INSERT INTO PAYMENT VALUES (13, 24.00, 'PayPal', 'Failed',   SYSDATE, 13, 11);
INSERT INTO PAYMENT VALUES (14, 11.00, 'PayPal', 'Completed', SYSDATE, 14, 12);
INSERT INTO PAYMENT VALUES (15, 33.00, 'PayPal', 'Completed', SYSDATE, 15, 13);
INSERT INTO PAYMENT VALUES (16, 11.50, 'PayPal', 'Failed',   SYSDATE, 16, 14);
INSERT INTO PAYMENT VALUES (17, 14.00, 'PayPal', 'Completed', SYSDATE, 17, 15);
INSERT INTO PAYMENT VALUES (18, 14.50, 'PayPal', 'Failed',   SYSDATE, 18, 16);
INSERT INTO PAYMENT VALUES (19, 35.00, 'PayPal', 'Completed', SYSDATE, 19, 17);
INSERT INTO PAYMENT VALUES (20, 12.00, 'PayPal', 'Completed', SYSDATE, 20, 18);


-- New trader users
INSERT INTO HUDDER_USER VALUES (31, 'James', 'Bennett', 'james@mail.com', 'pass123', 'trader', '0711500001', 'Huddersfield', TO_DATE('1988-04-22','YYYY-MM-DD'), 'Male', NULL);
INSERT INTO HUDDER_USER VALUES (32, 'Ava', 'Simpson', 'ava@mail.com', 'pass123', 'trader', '0711500002', 'Bradford', TO_DATE('1993-09-15','YYYY-MM-DD'), 'Female', NULL);
INSERT INTO HUDDER_USER VALUES (33, 'Marcus', 'Fletcher', 'marcus@mail.com', 'pass123', 'trader', '0711500003', 'Leeds', TO_DATE('1991-12-03','YYYY-MM-DD'), 'Male', NULL);

-- Assign as traders (Pending — waiting for admin approval)
INSERT INTO TRADER VALUES (6, 31, 'Pending');
INSERT INTO TRADER VALUES (7, 32, 'Pending');
INSERT INTO TRADER VALUES (8, 33, 'Pending');

COMMIT;


/*********************************************************************************************** SOME CHANGES *************************************************************************************************************************/ 
-- New trader user
INSERT INTO HUDDER_USER VALUES (19, 'Fiona', 'Cooper', 'fiona@mail.com', 'pass123', 'trader', '0711334466', 'Bradford', TO_DATE('1989-06-14','YYYY-MM-DD'), 'Female', NULL);
 
-- Register as trader (Active so her shop shows up)
INSERT INTO TRADER VALUES (5, 19, 'Active');
 
-- Reassign Bradford Bakery from Oliver (user_id=1) → Fiona (user_id=19)
UPDATE SHOP SET user_id = 19, contact_number = '0711334466'
WHERE shop_id = 5;

/* ─────────────────────────────────────────────────────────────
   MORE PRODUCTS  (2 per shop, status = 'Active')
   ───────────────────────────────────────────────────────────── */
 
INSERT INTO PRODUCT VALUES (6, 'Brie', 'Creamy French-style brie', 6.50, 80, 1, 8, 5, 'Dairy', 1, 1, NULL, 'Active');
INSERT INTO PRODUCT VALUES (7, 'Scotch Egg', 'Hand-rolled with pork sausage', 3.50, 55, 1, 12, 8, 'Eggs, Gluten', 1, 1, NULL, 'Active');
INSERT INTO PRODUCT VALUES (8, 'Lamb Chops', 'Yorkshire hill-farm lamb', 18.00, 40, 1, 4, 3, 'None', 2, 2, NULL, 'Active');
INSERT INTO PRODUCT VALUES (9, 'Pork Sausages', 'Traditional Cumberland sausages', 7.00, 90, 1, 6, 5, 'Gluten', 2, 2, NULL, 'Active');
INSERT INTO PRODUCT VALUES (10, 'Spinach', 'Baby spinach leaves, 250g', 2.50, 35, 1, 10, 15, 'None', 3, 3, NULL, 'Active');
INSERT INTO PRODUCT VALUES (11, 'Tomatoes', 'Vine-ripened tomatoes', 3.00, 44, 2, 15, 12, 'None', 3, 3, NULL, 'Active');
INSERT INTO PRODUCT VALUES (12, 'Cod Fillet', 'Line-caught Atlantic cod', 12.00, 50, 1, 4, 4, 'Fish', 4, 4, NULL, 'Active');
INSERT INTO PRODUCT VALUES (13, 'Tiger Prawns', 'Raw shell-on tiger prawns', 16.00, 35, 1, 3, 3, 'Shellfish', 4, 4, NULL, 'Active');
INSERT INTO PRODUCT VALUES (14, 'Croissant', 'Butter croissant, freshly baked', 2.50, 16, 1, 8, 6, 'Gluten, Dairy', 5, 5, NULL, 'Active');
INSERT INTO PRODUCT VALUES (15, 'Sourdough', 'Long-fermented sourdough loaf', 4.50, 70, 1, 6, 5, 'Gluten', 5, 5, NULL, 'Active');
 
/* ─────────────────────────────────────────────────────────────
   3.  MORE COLLECTION SLOTS 
   ───────────────────────────────────────────────────────────── */
 
INSERT INTO COLLECTION_SLOT VALUES (9,  TO_DATE('2026-05-06','YYYY-MM-DD'), '10:00-13:00', 20, 'Huddersfield');
INSERT INTO COLLECTION_SLOT VALUES (10, TO_DATE('2026-05-07','YYYY-MM-DD'), '13:00-16:00', 20, 'Cleckheaton');
INSERT INTO COLLECTION_SLOT VALUES (11, TO_DATE('2026-05-08','YYYY-MM-DD'), '16:00-19:00', 20, 'Halifax');
INSERT INTO COLLECTION_SLOT VALUES (12, TO_DATE('2026-05-13','YYYY-MM-DD'), '10:00-13:00', 20, 'Bradford');
INSERT INTO COLLECTION_SLOT VALUES (13, TO_DATE('2026-05-14','YYYY-MM-DD'), '13:00-16:00', 20, 'Leeds');
INSERT INTO COLLECTION_SLOT VALUES (14, TO_DATE('2026-05-15','YYYY-MM-DD'), '16:00-19:00', 20, 'Huddersfield');
 
 
/* ─────────────────────────────────────────────────────────────
   4.  10 NEW CUSTOMERS  (user_ids 21–30)
   ───────────────────────────────────────────────────────────── */
 
INSERT INTO HUDDER_USER VALUES (21, 'Poppy',   'Reed',     'poppy@mail.com',   'pass123', 'customer', '0711000001', 'Huddersfield', TO_DATE('1996-04-12','YYYY-MM-DD'), 'Female', NULL);
INSERT INTO HUDDER_USER VALUES (22, 'Freddie', 'Wood',     'freddie@mail.com', 'pass123', 'customer', '0711000002', 'Cleckheaton',  TO_DATE('1994-07-08','YYYY-MM-DD'), 'Male', NULL);
INSERT INTO HUDDER_USER VALUES (23, 'Daisy',   'Shaw',     'daisy@mail.com',   'pass123', 'customer', '0711000003', 'Halifax',      TO_DATE('2001-01-19','YYYY-MM-DD'), 'Female', NULL);
INSERT INTO HUDDER_USER VALUES (24, 'Oscar',   'Hughes',   'oscar@mail.com',   'pass123', 'customer', '0711000004', 'Leeds',        TO_DATE('1998-10-31','YYYY-MM-DD'), 'Male', NULL);
INSERT INTO HUDDER_USER VALUES (25, 'Lily',    'Price',    'lily@mail.com',    'pass123', 'customer', '0711000005', 'Bradford',     TO_DATE('2000-03-25','YYYY-MM-DD'), 'Female', NULL);
INSERT INTO HUDDER_USER VALUES (26, 'Henry',   'Collins',  'henry@mail.com',   'pass123', 'customer', '0711000006', 'Huddersfield', TO_DATE('1992-11-02','YYYY-MM-DD'), 'Male', NULL);
INSERT INTO HUDDER_USER VALUES (27, 'Isabelle','Morris',   'isabelle@mail.com','pass123', 'customer', '0711000007', 'Cleckheaton',  TO_DATE('1999-06-17','YYYY-MM-DD'), 'Female', NULL);
INSERT INTO HUDDER_USER VALUES (28, 'Archie',  'Rogers',   'archie@mail.com',  'pass123', 'customer', '0711000008', 'Halifax',      TO_DATE('1995-09-09','YYYY-MM-DD'), 'Male', NULL);
INSERT INTO HUDDER_USER VALUES (29, 'Scarlett','Cook',     'scarlett@mail.com','pass123', 'customer', '0711000009', 'Leeds',        TO_DATE('2002-02-14','YYYY-MM-DD'), 'Female', NULL);
INSERT INTO HUDDER_USER VALUES (30, 'Leo',     'Mitchell', 'leo@mail.com',     'pass123', 'customer', '0711000010', 'Bradford',     TO_DATE('1997-12-28','YYYY-MM-DD'), 'Male', NULL);
 
-- Assign customer roles  (continuing from customer_id 14 where the existing data left off)
INSERT INTO CUSTOMER VALUES (14, 21);
INSERT INTO CUSTOMER VALUES (15, 22);
INSERT INTO CUSTOMER VALUES (16, 23);
INSERT INTO CUSTOMER VALUES (17, 24);
INSERT INTO CUSTOMER VALUES (18, 25);
INSERT INTO CUSTOMER VALUES (19, 26);
INSERT INTO CUSTOMER VALUES (20, 27);
INSERT INTO CUSTOMER VALUES (21, 28);
INSERT INTO CUSTOMER VALUES (22, 29);
INSERT INTO CUSTOMER VALUES (23, 30);
 
 
/* ─────────────────────────────────────────────────────────────
   5.  CARTS FOR NEW CUSTOMERS
   ───────────────────────────────────────────────────────────── */
 
INSERT INTO CART VALUES (16, SYSDATE, 21);
INSERT INTO CART VALUES (17, SYSDATE, 22);
INSERT INTO CART VALUES (18, SYSDATE, 23);
INSERT INTO CART VALUES (19, SYSDATE, 24);
INSERT INTO CART VALUES (20, SYSDATE, 25);
INSERT INTO CART VALUES (21, SYSDATE, 26);
INSERT INTO CART VALUES (22, SYSDATE, 27);
INSERT INTO CART VALUES (23, SYSDATE, 28);
INSERT INTO CART VALUES (24, SYSDATE, 29);
INSERT INTO CART VALUES (25, SYSDATE, 30);
 
-- Cart items 
INSERT INTO CART_ITEM VALUES (16, 2, 16, 6);    
INSERT INTO CART_ITEM VALUES (17, 1, 17, 8);    
INSERT INTO CART_ITEM VALUES (18, 3, 18, 10);   
INSERT INTO CART_ITEM VALUES (19, 1, 19, 12);   
INSERT INTO CART_ITEM VALUES (20, 2, 20, 14);   
INSERT INTO CART_ITEM VALUES (21, 1, 21, 7);   
INSERT INTO CART_ITEM VALUES (22, 2, 22, 9);    
INSERT INTO CART_ITEM VALUES (23, 1, 23, 11);  
INSERT INTO CART_ITEM VALUES (24, 2, 24, 13);  
INSERT INTO CART_ITEM VALUES (25, 1, 25, 15);   
 
 
/* ─────────────────────────────────────────────────────────────
   6.  ORDERS FOR NEW CUSTOMERS
       Spread across the new May slots (9–14)
   ───────────────────────────────────────────────────────────── */
 
INSERT INTO HUDDER_ORDER VALUES (21, SYSDATE, '09:00 AM', 'Pending',   21, 9);
INSERT INTO HUDDER_ORDER VALUES (22, SYSDATE, '10:00 AM', 'Completed', 22, 10);
INSERT INTO HUDDER_ORDER VALUES (23, SYSDATE, '11:00 AM', 'Pending',   23, 11);
INSERT INTO HUDDER_ORDER VALUES (24, SYSDATE, '12:00 PM', 'Completed', 24, 12);
INSERT INTO HUDDER_ORDER VALUES (25, SYSDATE, '01:00 PM', 'Cancelled', 25, 13);
INSERT INTO HUDDER_ORDER VALUES (26, SYSDATE, '02:00 PM', 'Pending',   26, 14);
INSERT INTO HUDDER_ORDER VALUES (27, SYSDATE, '09:30 AM', 'Completed', 27, 9);
INSERT INTO HUDDER_ORDER VALUES (28, SYSDATE, '10:30 AM', 'Pending',   28, 10);
INSERT INTO HUDDER_ORDER VALUES (29, SYSDATE, '11:30 AM', 'Completed', 29, 11);
INSERT INTO HUDDER_ORDER VALUES (30, SYSDATE, '01:30 PM', 'Pending',   30, 12);
 
 
/* ─────────────────────────────────────────────────────────────
   7.  ORDER PRODUCTS  (mix new products with originals)
   ───────────────────────────────────────────────────────────── */
 
INSERT INTO ORDER_PRODUCT VALUES (21, 6,  2,  6.50);   
INSERT INTO ORDER_PRODUCT VALUES (21, 7,  3,  3.50);   
INSERT INTO ORDER_PRODUCT VALUES (22, 8,  1, 18.00);   
INSERT INTO ORDER_PRODUCT VALUES (22, 9,  2,  7.00);  
INSERT INTO ORDER_PRODUCT VALUES (23, 10, 4,  2.50);   
INSERT INTO ORDER_PRODUCT VALUES (23, 11, 3,  3.00);   
INSERT INTO ORDER_PRODUCT VALUES (24, 12, 2, 12.00);   
INSERT INTO ORDER_PRODUCT VALUES (24, 13, 1, 16.00);   
INSERT INTO ORDER_PRODUCT VALUES (25, 14, 4,  2.50);   
INSERT INTO ORDER_PRODUCT VALUES (25, 15, 1,  4.50);   
INSERT INTO ORDER_PRODUCT VALUES (26, 6,  1,  6.50);   
INSERT INTO ORDER_PRODUCT VALUES (27, 2,  2, 20.00); 
INSERT INTO ORDER_PRODUCT VALUES (27, 8,  1, 18.00);  
INSERT INTO ORDER_PRODUCT VALUES (28, 3,  5,  2.00);   
INSERT INTO ORDER_PRODUCT VALUES (28, 10, 2,  2.50);   
INSERT INTO ORDER_PRODUCT VALUES (29, 4,  1, 15.00);   
INSERT INTO ORDER_PRODUCT VALUES (29, 12, 2, 12.00);   
INSERT INTO ORDER_PRODUCT VALUES (30, 5,  2,  3.00);   
INSERT INTO ORDER_PRODUCT VALUES (30, 15, 1,  4.50);   
 
 
/* ─────────────────────────────────────────────────────────────
   8.  PAYMENTS
   ───────────────────────────────────────────────────────────── */
 
INSERT INTO PAYMENT VALUES (21, 23.50, 'PayPal',  'Completed', SYSDATE, 21, 21);
INSERT INTO PAYMENT VALUES (22, 32.00, 'PayPal',  'Completed', SYSDATE, 22, 22);
INSERT INTO PAYMENT VALUES (23, 19.00, 'PayPal',  'Failed', SYSDATE, 23, 23);
INSERT INTO PAYMENT VALUES (24, 40.00, 'PayPal',  'Completed', SYSDATE, 24, 24);
INSERT INTO PAYMENT VALUES (25, 14.50, 'PayPal',  'Failed', SYSDATE, 25, 25);
INSERT INTO PAYMENT VALUES (26, 15.00, 'PayPal',  'Completed', SYSDATE, 26, 26);
INSERT INTO PAYMENT VALUES (27, 58.00, 'PayPal',  'Completed', SYSDATE, 27, 27);
INSERT INTO PAYMENT VALUES (28, 15.00, 'PayPal',  'Failed',   SYSDATE, 28, 28);
INSERT INTO PAYMENT VALUES (29, 39.00, 'PayPal',  'Completed', SYSDATE, 29, 29);
INSERT INTO PAYMENT VALUES (30, 10.50, 'PayPal',  'Completed', SYSDATE, 30, 30);
 
 
/* ─────────────────────────────────────────────────────────────
   9.  REVIEWS  (new customers reviewing products they ordered)
   ───────────────────────────────────────────────────────────── */
 
INSERT INTO REVIEW VALUES (16, 'Lovely creamy brie, great value', 4.7, 21, 6);
INSERT INTO REVIEW VALUES (17, 'Lamb was melt-in-the-mouth tender',4.8, 22, 8);
INSERT INTO REVIEW VALUES (18, 'Super fresh spinach, nice big bag',4.5, 23, 10);
INSERT INTO REVIEW VALUES (19, 'Cod was flaky and perfectly fresh',4.6, 24, 12);
INSERT INTO REVIEW VALUES (20, 'Croissants were still warm on collection',5.0, 25, 14);
INSERT INTO REVIEW VALUES (22, 'Both steaks were excellent quality',4.9, 27, 2);
INSERT INTO REVIEW VALUES (23, 'Carrots a bit earthy but very fresh',4.1, 28, 3);
INSERT INTO REVIEW VALUES (24, 'Salmon portion was generous and fresh',4.7, 29, 4);
INSERT INTO REVIEW VALUES (25, 'Sourdough crust was amazing',5.0, 30, 15);
 
 
/* ─────────────────────────────────────────────────────────────
   10.  DISCOUNTS  (spread across new products too)
   ───────────────────────────────────────────────────────────── */
 
INSERT INTO DISCOUNT VALUES (6,  12, 'Seasonal',  SYSDATE+30, 4,  6);   
INSERT INTO DISCOUNT VALUES (7,  8,  'Clearance', SYSDATE+10, 4,  8);   
INSERT INTO DISCOUNT VALUES (8,  10, 'Festival',  SYSDATE+20, 7,  10);  
INSERT INTO DISCOUNT VALUES (9,  15, 'Seasonal',  SYSDATE+35, 8,  12);  
INSERT INTO DISCOUNT VALUES (10, 20, 'Special',   SYSDATE+25, 1,  14);  
 
 
/* ─────────────────────────────────────────────────────────────
   11.  FAVOURITES
   ───────────────────────────────────────────────────────────── */
 
INSERT INTO FAVOURITE VALUES (1,  SYSDATE, 21, 6);    
INSERT INTO FAVOURITE VALUES (2,  SYSDATE, 22, 8);    
INSERT INTO FAVOURITE VALUES (3,  SYSDATE, 23, 10);  
INSERT INTO FAVOURITE VALUES (4,  SYSDATE, 24, 12);   
INSERT INTO FAVOURITE VALUES (5,  SYSDATE, 25, 14);       
INSERT INTO FAVOURITE VALUES (7,  SYSDATE, 27, 2);   
INSERT INTO FAVOURITE VALUES (8,  SYSDATE, 28, 3);   
INSERT INTO FAVOURITE VALUES (9,  SYSDATE, 29, 4);    
INSERT INTO FAVOURITE VALUES (10, SYSDATE, 30, 5);    
-- A few existing customers with favourites for new products
INSERT INTO FAVOURITE VALUES (11, SYSDATE, 2,  7);    
INSERT INTO FAVOURITE VALUES (12, SYSDATE, 5,  9);   
INSERT INTO FAVOURITE VALUES (13, SYSDATE, 6,  11);  
INSERT INTO FAVOURITE VALUES (14, SYSDATE, 9,  13);  
INSERT INTO FAVOURITE VALUES (15, SYSDATE, 10, 15);   
 
-- Pending products for admin approval 
INSERT INTO PRODUCT VALUES (16, 'Aged Cheddar', 'Mature 18-month cheddar', 8.50, 60, 1, 6, 5, 'Dairy', 1, 1, NULL, 'Pending');
INSERT INTO PRODUCT VALUES (17, 'Pork Ribs', 'Slow-cook pork spare ribs', 14.00, 45, 1, 4, 3, 'None', 2, 2, NULL, 'Pending');
INSERT INTO PRODUCT VALUES (18, 'Sweet Potato', 'Organic sweet potatoes 1kg', 3.50, 80, 2, 15, 10, 'None', 3, 3, NULL, 'Pending');
INSERT INTO PRODUCT VALUES (19, 'Smoked Mackerel', 'Hot-smoked whole mackerel', 9.00, 40, 1, 4, 4, 'Fish', 4, 4, NULL, 'Pending');
INSERT INTO PRODUCT VALUES (20, 'Cinnamon Roll', 'Freshly baked cinnamon roll', 2.80, 90, 1, 10, 6, 'Gluten, Dairy', 5, 5, NULL, 'Pending');
INSERT INTO PRODUCT VALUES (21, 'Stilton', 'Creamy blue stilton wedge', 7.00, 50, 1, 5, 4, 'Dairy', 1, 1, NULL, 'Pending');
INSERT INTO PRODUCT VALUES (22, 'Chicken Thighs', 'Free-range chicken thighs', 10.00, 70, 2, 8, 5, 'None', 2, 2, NULL, 'Pending');
INSERT INTO PRODUCT VALUES (23, 'Courgette', 'Farm-fresh courgettes', 2.00, 160, 2, 20, 12, 'None', 3, 3, NULL, 'Pending');
 
--Updating some datas
-- Orders 1-3 already have specific April dates set, leave those

-- Spread orders across May 2026 (keep orders 6,7,9,10,11,13,16,18,19,21,23,26,28,30 as SYSDATE)
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-05-01', 'YYYY-MM-DD') WHERE order_id = 4;
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-05-01', 'YYYY-MM-DD') WHERE order_id = 5;
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-05-11', 'YYYY-MM-DD') WHERE order_id = 8;
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-05-11', 'YYYY-MM-DD') WHERE order_id = 12;
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-05-11', 'YYYY-MM-DD') WHERE order_id = 14;
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-05-13', 'YYYY-MM-DD') WHERE order_id = 15;
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-05-13', 'YYYY-MM-DD') WHERE order_id = 17;
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-05-12', 'YYYY-MM-DD') WHERE order_id = 20;
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-05-10', 'YYYY-MM-DD') WHERE order_id = 22;
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-05-10', 'YYYY-MM-DD') WHERE order_id = 24;
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-05-12', 'YYYY-MM-DD') WHERE order_id = 25;
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-05-13', 'YYYY-MM-DD') WHERE order_id = 27;
UPDATE HUDDER_ORDER SET order_date = TO_DATE('2026-05-14', 'YYYY-MM-DD') WHERE order_id = 29;

ALTER TABLE SHOP ADD (mimetype VARCHAR2(255), filename VARCHAR2(255));