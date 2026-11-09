-- ATUALIZAÇÃO DA VERSÃO 1 -> 2. Faça exportação de segurança e feche o app antes.
-- Selecione o banco existente meu_treino no phpMyAdmin e importe ESTE arquivo.
-- Não execute schema.sql sobre o banco existente.
USE meu_treino;
CREATE TABLE IF NOT EXISTS schema_migrations (
 versao VARCHAR(50) PRIMARY KEY,
 aplicado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

DROP PROCEDURE IF EXISTS migrar_meu_treino_002;
DELIMITER $$
CREATE PROCEDURE migrar_meu_treino_002()
migracao: BEGIN
    DECLARE legados INT DEFAULT 0;
    DECLARE copiados INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
    IF EXISTS (SELECT 1 FROM schema_migrations WHERE versao='002_fluxos') THEN
        SELECT 'Migração 002 já aplicada. Nenhum dado foi alterado.' AS resultado;
        LEAVE migracao;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='treinos' AND COLUMN_NAME='exercicio_id') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Estrutura anterior inesperada. Pare e revise o banco; não apague dados.';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='nivel') THEN
        ALTER TABLE usuarios ADD COLUMN nivel ENUM('iniciante','intermediario','avancado') NOT NULL DEFAULT 'iniciante';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='objetivo') THEN
        ALTER TABLE usuarios ADD COLUMN objetivo ENUM('hipertrofia','forca','condicionamento','geral') NOT NULL DEFAULT 'geral';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='tempo_disponivel') THEN
        ALTER TABLE usuarios ADD COLUMN tempo_disponivel ENUM('30','60','90') NOT NULL DEFAULT '30';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='exercicios' AND COLUMN_NAME='video_url') THEN
        ALTER TABLE exercicios ADD COLUMN video_url VARCHAR(1000) NOT NULL DEFAULT '';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='treinos' AND COLUMN_NAME='grupo_muscular') THEN
        ALTER TABLE treinos ADD COLUMN grupo_muscular ENUM('peito','costas','pernas','ombros','bracos','triceps','abdomen') NULL;
    END IF;
    ALTER TABLE treinos MODIFY COLUMN exercicio_id INT UNSIGNED NULL,
        MODIFY COLUMN series SMALLINT UNSIGNED NULL,
        MODIFY COLUMN repeticoes SMALLINT UNSIGNED NULL,
        MODIFY COLUMN carga DECIMAL(6,2) NULL DEFAULT NULL;
CREATE TABLE IF NOT EXISTS treino_exercicios (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 treino_id INT UNSIGNED NOT NULL,
 exercicio_id INT UNSIGNED NULL,
 descricao_livre VARCHAR(500) NOT NULL DEFAULT '',
 series SMALLINT UNSIGNED NULL,
 repeticoes SMALLINT UNSIGNED NULL,
 carga DECIMAL(6,2) NULL,
 CONSTRAINT fk_item_treino FOREIGN KEY (treino_id) REFERENCES treinos(id) ON DELETE CASCADE,
 CONSTRAINT fk_item_exercicio FOREIGN KEY (exercicio_id) REFERENCES exercicios(id) ON DELETE RESTRICT,
 CONSTRAINT uq_item_catalogo UNIQUE (treino_id,exercicio_id),
 CONSTRAINT ck_item_origem CHECK ((exercicio_id IS NOT NULL AND descricao_livre='') OR (exercicio_id IS NULL AND CHAR_LENGTH(TRIM(descricao_livre))>0)),
 CONSTRAINT ck_item_series CHECK (series IS NULL OR series BETWEEN 1 AND 30),
 CONSTRAINT ck_item_repeticoes CHECK (repeticoes IS NULL OR repeticoes BETWEEN 1 AND 1000),
 CONSTRAINT ck_item_carga CHECK (carga IS NULL OR carga BETWEEN 0 AND 1000),
 INDEX idx_item_exercicio (exercicio_id)
) ENGINE=InnoDB;

    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='treinos' AND INDEX_NAME='idx_treino_usuario_grupo_data') THEN
        ALTER TABLE treinos ADD INDEX idx_treino_usuario_grupo_data (usuario_id,grupo_muscular,data_treino);
    END IF;
    START TRANSACTION;
    UPDATE treinos t JOIN exercicios e ON e.id=t.exercicio_id
        SET t.grupo_muscular=e.grupo_muscular WHERE t.grupo_muscular IS NULL;
    IF EXISTS (SELECT 1 FROM treinos WHERE grupo_muscular IS NULL) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Há treino sem grupo/exercício válido. Corrija antes de continuar.';
    END IF;
    INSERT INTO treino_exercicios(treino_id,exercicio_id,descricao_livre,series,repeticoes,carga)
        SELECT t.id,t.exercicio_id,'',t.series,t.repeticoes,t.carga FROM treinos t
        WHERE t.exercicio_id IS NOT NULL
          AND NOT EXISTS (SELECT 1 FROM treino_exercicios te WHERE te.treino_id=t.id AND te.exercicio_id=t.exercicio_id);
    SELECT COUNT(*) INTO legados FROM treinos WHERE exercicio_id IS NOT NULL;
    SELECT COUNT(*) INTO copiados FROM treinos t JOIN treino_exercicios te
        ON te.treino_id=t.id AND te.exercicio_id=t.exercicio_id
        WHERE (te.series <=> t.series) AND (te.repeticoes <=> t.repeticoes) AND (te.carga <=> t.carga);
    IF legados <> copiados THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Conferência dos itens antigos falhou. Nenhum treino deve ser apagado.';
    END IF;
    COMMIT;
    ALTER TABLE treinos MODIFY COLUMN grupo_muscular ENUM('peito','costas','pernas','ombros','bracos','triceps','abdomen') NOT NULL;
    -- A FK ativa agora está em treino_exercicios. As quatro colunas antigas ficam como cópia histórica.
    IF EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='treinos' AND CONSTRAINT_NAME='fk_treino_exercicio' AND CONSTRAINT_TYPE='FOREIGN KEY') THEN
        ALTER TABLE treinos DROP FOREIGN KEY fk_treino_exercicio;
    END IF;
    START TRANSACTION;
-- Inclui opções sem alterar exercícios já cadastrados pelo administrador. Sem URLs inventadas.
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Supino reto','peito','Opção do catálogo para peito.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Supino reto');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Supino inclinado','peito','Opção do catálogo para peito.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Supino inclinado');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Crucifixo','peito','Opção do catálogo para peito.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Crucifixo');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Supino com halteres','peito','Opção do catálogo para peito.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Supino com halteres');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Crossover','peito','Opção do catálogo para peito.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Crossover');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Flexão de braços','peito','Opção do catálogo para peito.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Flexão de braços');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Puxada frontal','costas','Opção do catálogo para costas.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Puxada frontal');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Remada baixa','costas','Opção do catálogo para costas.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Remada baixa');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Remada curvada','costas','Opção do catálogo para costas.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Remada curvada');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Remada unilateral','costas','Opção do catálogo para costas.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Remada unilateral');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Remada máquina','costas','Opção do catálogo para costas.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Remada máquina');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Pulldown','costas','Opção do catálogo para costas.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Pulldown');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Agachamento','pernas','Opção do catálogo para pernas.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Agachamento');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Leg press','pernas','Opção do catálogo para pernas.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Leg press');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Cadeira extensora','pernas','Opção do catálogo para pernas.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Cadeira extensora');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Mesa flexora','pernas','Opção do catálogo para pernas.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Mesa flexora');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Afundo','pernas','Opção do catálogo para pernas.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Afundo');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Elevação de panturrilhas','pernas','Opção do catálogo para pernas.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Elevação de panturrilhas');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Desenvolvimento','ombros','Opção do catálogo para ombros.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Desenvolvimento');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Elevação lateral','ombros','Opção do catálogo para ombros.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Elevação lateral');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Elevação frontal','ombros','Opção do catálogo para ombros.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Elevação frontal');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Crucifixo inverso','ombros','Opção do catálogo para ombros.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Crucifixo inverso');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Desenvolvimento com halteres','ombros','Opção do catálogo para ombros.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Desenvolvimento com halteres');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Elevação lateral no cabo','ombros','Opção do catálogo para ombros.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Elevação lateral no cabo');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Rosca direta','bracos','Opção do catálogo para bíceps.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Rosca direta');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Rosca martelo','bracos','Opção do catálogo para bíceps.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Rosca martelo');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Rosca inversa','bracos','Opção do catálogo para bíceps.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Rosca inversa');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Rosca alternada','bracos','Opção do catálogo para bíceps.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Rosca alternada');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Rosca concentrada','bracos','Opção do catálogo para bíceps.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Rosca concentrada');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Rosca Scott','bracos','Opção do catálogo para bíceps.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Rosca Scott');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Tríceps pulley','triceps','Opção do catálogo para tríceps.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Tríceps pulley');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Tríceps francês','triceps','Opção do catálogo para tríceps.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Tríceps francês');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Tríceps testa','triceps','Opção do catálogo para tríceps.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Tríceps testa');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Tríceps corda','triceps','Opção do catálogo para tríceps.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Tríceps corda');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Extensão unilateral no cabo','triceps','Opção do catálogo para tríceps.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Extensão unilateral no cabo');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Tríceps coice','triceps','Opção do catálogo para tríceps.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Tríceps coice');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Abdominal','abdomen','Opção do catálogo para abdômen.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Abdominal');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Elevação de pernas','abdomen','Opção do catálogo para abdômen.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Elevação de pernas');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Prancha','abdomen','Opção do catálogo para abdômen.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Prancha');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Abdominal reverso','abdomen','Opção do catálogo para abdômen.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Abdominal reverso');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Abdominal bicicleta','abdomen','Opção do catálogo para abdômen.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Abdominal bicicleta');
INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) SELECT 'Abdominal no cabo','abdomen','Opção do catálogo para abdômen.','' WHERE NOT EXISTS (SELECT 1 FROM exercicios WHERE nome='Abdominal no cabo');
    INSERT INTO schema_migrations(versao) VALUES ('002_fluxos');
    COMMIT;
    SELECT 'Migração 002 concluída. Usuários, hashes, IDs e horários anteriores preservados.' AS resultado, legados AS treinos_anteriores, copiados AS itens_copiados;
END$$
DELIMITER ;
CALL migrar_meu_treino_002();
DROP PROCEDURE IF EXISTS migrar_meu_treino_002;
