ALTER TABLE users 
ADD COLUMN status ENUM('pending', 'active', 'inactive') DEFAULT 'pending' 
AFTER password; 