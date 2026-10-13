-- Initial Master Data for Sports Junction B2B Marketplace

-- 1. Countries
INSERT INTO countries (name, iso_alpha2, iso_alpha3, phone_code, status) VALUES
('India', 'IN', 'IND', '+91', 'ACTIVE'),
('United States', 'US', 'USA', '+1', 'ACTIVE'),
('United Kingdom', 'GB', 'GBR', '+44', 'ACTIVE'),
('Germany', 'DE', 'DEU', '+49', 'ACTIVE'),
('Australia', 'AU', 'AUS', '+61', 'ACTIVE'),
('Japan', 'JP', 'JPN', '+81', 'ACTIVE')
ON DUPLICATE KEY UPDATE name=name;

-- 2. States (India sports manufacturing hubs and key states)
INSERT INTO states (country_id, name, code, status) VALUES
(1, 'Uttar Pradesh', 'UP', 'ACTIVE'),
(1, 'Punjab', 'PB', 'ACTIVE'),
(1, 'Maharashtra', 'MH', 'ACTIVE'),
(1, 'Karnataka', 'KA', 'ACTIVE'),
(1, 'Delhi', 'DL', 'ACTIVE'),
(1, 'Gujarat', 'GJ', 'ACTIVE'),
(1, 'Tamil Nadu', 'TN', 'ACTIVE'),
(1, 'Haryana', 'HR', 'ACTIVE'),
(1, 'Rajasthan', 'RJ', 'ACTIVE'),
(1, 'West Bengal', 'WB', 'ACTIVE')
ON DUPLICATE KEY UPDATE name=name;

-- 3. Cities (Meerut & Jalandhar are premier sports manufacturing clusters in India!)
INSERT INTO cities (country_id, state_id, name, status) VALUES
(1, 1, 'Meerut', 'ACTIVE'),        -- Premier sports goods cluster
(1, 1, 'Noida', 'ACTIVE'),
(1, 1, 'Agra', 'ACTIVE'),
(1, 2, 'Jalandhar', 'ACTIVE'),     -- World renowned sports manufacturing cluster
(1, 2, 'Ludhiana', 'ACTIVE'),
(1, 3, 'Mumbai', 'ACTIVE'),
(1, 3, 'Pune', 'ACTIVE'),
(1, 4, 'Bengaluru', 'ACTIVE'),
(1, 5, 'New Delhi', 'ACTIVE'),
(1, 6, 'Ahmedabad', 'ACTIVE'),
(1, 7, 'Chennai', 'ACTIVE'),
(1, 8, 'Gurugram', 'ACTIVE')
ON DUPLICATE KEY UPDATE name=name;

-- 4. Business Types
INSERT INTO business_types (name, description, status) VALUES
('Manufacturer', 'Direct manufacturers producing sports gear, balls, equipment, and sportswear', 'ACTIVE'),
('Trading Company', 'Trading entities supplying specialized sporting goods across domestic and global markets', 'ACTIVE'),
('Wholesaler', 'Bulk inventory distributors supplying retail networks and institutions', 'ACTIVE'),
('Distributor', 'Authorized brand and cluster distribution agents', 'ACTIVE'),
('Exporter', 'Export houses shipping sports manufacturing goods internationally', 'ACTIVE'),
('Other', 'Other allied sports industry service and manufacturing enterprises', 'ACTIVE')
ON DUPLICATE KEY UPDATE name=name;

-- 5. Price Units
INSERT INTO price_units (name, symbol, description, status) VALUES
('Piece', 'pc', 'Per individual piece / item unit', 'ACTIVE'),
('Set', 'set', 'Per packaged set / kit', 'ACTIVE'),
('Box', 'box', 'Per standard packaging box', 'ACTIVE'),
('Kilogram', 'kg', 'Per kilogram weight measurement', 'ACTIVE'),
('Ton', 'ton', 'Per metric ton bulk measurement', 'ACTIVE'),
('Meter', 'm', 'Per linear meter length for synthetic turf and fabrics', 'ACTIVE'),
('Dozen', 'doz', 'Per dozen (pack of 12 units)', 'ACTIVE')
ON DUPLICATE KEY UPDATE name=name;

-- 6. MOQ Units
INSERT INTO moq_units (name, symbol, description, status) VALUES
('Pieces', 'pcs', 'Minimum order in individual units', 'ACTIVE'),
('Sets', 'sets', 'Minimum order in complete sets', 'ACTIVE'),
('Boxes', 'boxes', 'Minimum order in standard boxed lots', 'ACTIVE'),
('Kilograms', 'kgs', 'Minimum order in kilograms', 'ACTIVE'),
('Tons', 'tons', 'Minimum order in metric tons', 'ACTIVE'),
('Meters', 'meters', 'Minimum order in linear meters', 'ACTIVE'),
('Dozens', 'dozens', 'Minimum order in 12-item packs', 'ACTIVE')
ON DUPLICATE KEY UPDATE name=name;

-- 7. Sample Suppliers (to test protected business dependency validation)
INSERT INTO suppliers (business_type_id, country_id, state_id, city_id, company_name, contact_person, email, phone, address, status) VALUES
(1, 1, 1, 1, 'Meerut Sports Gear Ltd', 'Rajesh Sharma', 'meerut.sports@example.com', '+91 9876543210', 'Suraj Kund Sports Complex, Meerut', 'ACTIVE'),
(1, 1, 2, 4, 'Jalandhar International Inflatables', 'Harpreet Singh', 'jalandhar.balls@example.com', '+91 9812345678', 'Leather & Sports Complex, Jalandhar', 'ACTIVE')
ON DUPLICATE KEY UPDATE company_name=company_name;

-- 8. Sample Products (referencing Price Unit and MOQ Unit)
INSERT INTO products (supplier_id, title, price, price_unit_id, moq_quantity, moq_unit_id, country_id, state_id, city_id, status) VALUES
(1, 'Grade-A English Willow Cricket Bat', 4500.00, 1, 25, 1, 1, 1, 1, 'ACTIVE'),
(2, 'FIFA Quality Pro Synthetic Leather Football', 850.00, 1, 100, 1, 1, 2, 4, 'ACTIVE')
ON DUPLICATE KEY UPDATE title=title;
