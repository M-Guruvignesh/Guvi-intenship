USE guvi_internship;

-- Password: Test@1234
INSERT INTO users (name, email, password_hash)
VALUES (
  'Test User',
  'test@example.com',
  '$2y$10$Qe5WhP8pk2I6hUPM2CeqeeE4l3F2EAx3yIY7RzRfQ6bnPO3xaRVaG'
);
