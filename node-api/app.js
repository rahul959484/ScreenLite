const express = require('express');
const deviceRoutes = require('./routes/deviceRoutes');

const app = express();

app.use(express.json({ limit: '10kb' }));
app.use('/v1/devices', deviceRoutes);

app.use((req, res) => {
  res.status(404).json({
    success: false,
    message: 'Route not found',
  });
});

app.use((error, req, res, next) => {
  if (error.type === 'entity.parse.failed') {
    return res.status(400).json({
      success: false,
      message: 'Request body must contain valid JSON',
    });
  }

  console.error('ScreenLite API request failed:', error);
  return res.status(500).json({
    success: false,
    message: 'Internal server error',
  });
});

module.exports = app;
