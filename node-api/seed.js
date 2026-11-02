require('dotenv').config();

const pool = require('./config/database');

const sampleDeviceCode = 'SCR-SAMPLE000001';
const sampleItems = [
  {
    mediaUrl: 'https://example.com/media/welcome-video.mp4',
    durationSeconds: 30,
    position: 1,
  },
  {
    mediaUrl: 'https://example.com/media/store-hours.jpg',
    durationSeconds: 15,
    position: 2,
  },
];

async function seed() {
  let connection;

  try {
    connection = await pool.getConnection();
    await connection.beginTransaction();

    await connection.execute(
      `INSERT INTO screens (name, device_code)
       VALUES (?, ?)
       ON DUPLICATE KEY UPDATE name = VALUES(name)`,
      ['Sample Screen', sampleDeviceCode],
    );

    const [screens] = await connection.execute(
      'SELECT id FROM screens WHERE device_code = ? FOR UPDATE',
      [sampleDeviceCode],
    );
    const screenId = screens[0].id;

    const [counts] = await connection.execute(
      'SELECT COUNT(*) AS item_count FROM playlist_items WHERE screen_id = ?',
      [screenId],
    );

    if (Number(counts[0].item_count) === 0) {
      for (const item of sampleItems) {
        await connection.execute(
          `INSERT INTO playlist_items
             (screen_id, media_url, duration_seconds, position)
           VALUES (?, ?, ?, ?)`,
          [screenId, item.mediaUrl, item.durationSeconds, item.position],
        );
      }
      console.log('Created the sample screen and playlist.');
    } else {
      console.log('The sample screen already has playlist items; no duplicates added.');
    }

    await connection.commit();
    console.log(`Sample device code: ${sampleDeviceCode}`);
  } catch (error) {
    if (connection) {
      await connection.rollback();
    }
    throw error;
  } finally {
    if (connection) {
      connection.release();
    }
    await pool.end();
  }
}

seed().catch((error) => {
  console.error('Could not seed the ScreenLite database:', error);
  process.exitCode = 1;
});
