-- Doctrine Migration File Generated on 2026-01-02 16:57:21

-- Version DoctrineMigrations\Version20260102165701
ALTER TABLE category ADD parent_id INT DEFAULT NULL;
ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id);
CREATE INDEX IDX_64C19C1727ACA70 ON category (parent_id);
