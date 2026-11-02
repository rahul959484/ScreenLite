const express = require('express');
const pool = require('../config/database');

const router = express.Router();

function isValidDeviceCode(deviceCode) {
  return typeof deviceCode === 'string'
    && deviceCode.trim().length > 0
    && deviceCode.length <= 64;
}

router.post('/heartbeat', async (req, res, next) => {
  const { device_code: deviceCode } = req.body ?? {};

  if (!isValidDeviceCode(deviceCode)) {
    return res.status(400).json({
      success: false,
      message: 'A valid device_code is required',
    });
  }

  try {
    const [screens] = await pool.execute(
      'SELECT id FROM screens WHERE device_code = ? LIMIT 1',
      [deviceCode.trim()],
    );

    if (screens.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Device not found',
      });
    }

    await pool.execute(
      'UPDATE screens SET last_seen_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 330 MINUTE) WHERE id = ?',
      [screens[0].id],
    );

    return res.status(200).json({
      success: true,
      message: 'Heartbeat received successfully',
    });
  } catch (error) {
    return next(error);
  }
});

router.get('/:deviceCode/playlist', async (req, res, next) => {
  const { deviceCode } = req.params;

  if (!isValidDeviceCode(deviceCode)) {
    return res.status(400).json({
      success: false,
      message: 'A valid device code is required',
    });
  }

  try {
    const [screens] = await pool.execute(
      'SELECT id FROM screens WHERE device_code = ? LIMIT 1',
      [deviceCode.trim()],
    );

    if (screens.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Device not found',
      });
    }

    const [items] = await pool.execute(
      `SELECT id, media_url, duration_seconds, position
       FROM playlist_items
       WHERE screen_id = ?
       ORDER BY position ASC`,
      [screens[0].id],
    );

    return res.status(200).json({
      success: true,
      data: items,
    });
  } catch (error) {
    return next(error);
  }
});

module.exports = router;
