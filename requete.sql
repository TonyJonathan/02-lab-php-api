-- Création de la table "technologies"
CREATE TABLE technologies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

-- Création de la table "categories"
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

-- Insertion des catégories
INSERT INTO categories (name) VALUES
    ('Langages de programmation'),
    ('Frameworks et bibliothèques'),
    ('Base de données'),
    ('Développement frontal'),
    ('Développement back-end'),
    ('Sécurité web'),
    ('Déploiement et gestion de serveurs'),
    ('Développement mobile et responsive'),
    ('Outils de développement'),
    ('Tendances et nouveautés'),
    ('Communauté et ressources d''apprentissage');

-- Création de la table "technologies_categories" pour la relation many-to-many
CREATE TABLE technologies_categories (
    technology_id INT,
    category_id INT,
    PRIMARY KEY (technology_id, category_id),
    FOREIGN KEY (technology_id) REFERENCES technologies(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);