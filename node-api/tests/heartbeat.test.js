const request = require('supertest');
const app = require('../app');

describe('POST /v1/devices/heartbeat', () => {
  test('returns 400 when device_code is missing', async () => {
    const response = await request(app)
      .post('/v1/devices/heartbeat')
      .send({});

    expect(response.status).toBe(400);
    expect(response.body).toEqual({
      success: false,
      message: 'A valid device_code is required',
    });
  });

  test('returns 400 when device_code is not a string', async () => {
    const response = await request(app)
      .post('/v1/devices/heartbeat')
      .send({ device_code: 123 });

    expect(response.status).toBe(400);
    expect(response.body.success).toBe(false);
  });
});
