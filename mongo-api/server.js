const express = require('express');
const mongoose = require('mongoose');
const cors = require('cors');
require('dotenv').config();

const app = express();
app.use(cors());
app.use(express.json());

const mongoUrl = process.env.MONGO_URL;
if (!mongoUrl) {
  console.error('MONGO_URL is missing. Add it in Render environment variables.');
  process.exit(1);
}

mongoose
  .connect(mongoUrl, { serverSelectionTimeoutMS: 10000 })
  .then(() => console.log('MongoDB connected'))
  .catch((err) => {
    console.error('MongoDB connection error:', err.message);
    process.exit(1);
  });

const profileSchema = new mongoose.Schema(
  {
    user_id: { type: Number, required: true, unique: true, index: true },
    age: { type: Number, default: null },
    contact: { type: String, default: '' },
    dob: { type: String, default: '' }
  },
  { timestamps: true, collection: 'profiles' }
);

const Profile = mongoose.model('Profile', profileSchema);

app.get('/', (_req, res) => {
  res.json({ success: true, message: 'GUVI profile Mongo API is running' });
});

app.get('/profile', async (req, res) => {
  try {
    const userId = Number(req.query.user_id);
    if (!Number.isInteger(userId) || userId <= 0) {
      return res.status(400).json({ success: false, error: 'Valid user_id is required' });
    }

    const profile = await Profile.findOne({ user_id: userId }).lean();
    res.json({
      success: true,
      profile: profile
        ? {
            user_id: profile.user_id,
            age: profile.age ?? '',
            contact: profile.contact ?? '',
            dob: profile.dob ?? ''
          }
        : { age: '', contact: '', dob: '' }
    });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

app.post('/profile', async (req, res) => {
  try {
    const userId = Number(req.body.user_id);
    if (!Number.isInteger(userId) || userId <= 0) {
      return res.status(400).json({ success: false, error: 'Valid user_id is required' });
    }

    const age = req.body.age === null || req.body.age === '' ? null : Number(req.body.age);
    const contact = String(req.body.contact || '').trim();
    const dob = String(req.body.dob || '').trim();

    await Profile.updateOne(
      { user_id: userId },
      { $set: { user_id: userId, age, contact, dob } },
      { upsert: true }
    );

    res.json({ success: true, message: 'Mongo profile saved' });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

const port = process.env.PORT || 3000;
app.listen(port, () => console.log(`Server running on port ${port}`));
