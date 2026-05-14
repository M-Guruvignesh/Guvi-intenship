// Run this script inside the "guvi_internship" database in MongoDB Compass.
// Make the schema script safe to run multiple times.
if (db.getCollectionNames().includes("profiles")) {
  db.profiles.drop();
}

db.createCollection("profiles", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["user_id"],
      properties: {
        user_id: { bsonType: "int" },
        age: { bsonType: ["int", "null"], minimum: 1, maximum: 120 },
        dob: { bsonType: ["string", "null"] },
        contact: { bsonType: ["string", "null"] }
      }
    }
  }
});

db.profiles.createIndex({ user_id: 1 }, { unique: true });
