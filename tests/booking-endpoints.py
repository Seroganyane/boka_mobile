"""Local HTTP checks with test-only credentials; no payments or emails are sent."""
import base64
import hashlib
import hmac
import json
import os
from pathlib import Path
import socket
import subprocess
import time
from urllib.error import HTTPError
from urllib.request import Request, urlopen

root = Path(__file__).resolve().parent.parent
secret = 'local-test-only-signing-secret-123456789'
env = dict(os.environ, TRAVEL_QUOTE_SECRET='', GOOGLE_MAPS_API_KEY='',
           PAYFAST_MERCHANT_ID='test-merchant', PAYFAST_MERCHANT_KEY='test-key',
           PAYFAST_SANDBOX='true', APP_URL='https://example.test')
with socket.socket() as listener:
    listener.bind(('127.0.0.1', 0))
    port = listener.getsockname()[1]
server = subprocess.Popen(['php', '-S', f'127.0.0.1:{port}', '-t', str(root)],
                          env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

def post(endpoint, data):
    request = Request(f'http://127.0.0.1:{port}/php/{endpoint}.php',
                      data=json.dumps(data).encode(), headers={'Content-Type': 'application/json'})
    try:
        with urlopen(request, timeout=5) as response:
            return response.status, json.load(response)
    except HTTPError as error:
        return error.code, json.load(error)

try:
    for _ in range(50):
        try:
            with socket.create_connection(('127.0.0.1', port), timeout=.2):
                break
        except OSError:
            time.sleep(.1)
    customer = dict(name='Test Customer', phone='0650000000', email='test@example.test',
                    category='sedan', package='sedan:0', date='2026-12-01', visitType='dropoff', amount='R1')
    status, checkout = post('initialize-payment', customer)
    assert status == 200, checkout
    assert checkout['fields']['amount'] == '700.00'
    assert checkout['fields']['custom_str4'] == 'dropoff'
    assert checkout['process_url'] == 'https://sandbox.payfast.co.za/eng/process'
    customer.update(visitType='housecall', location='10 Example Street, Kempton Park')
    customer['distanceKm'] = '12.5'
    status, checkout = post('initialize-payment', customer)
    assert status == 200, checkout
    assert checkout['fields']['amount'] == '762.50'
    assert checkout['fields']['custom_str5'] == 'manual:12500'
    customer['distanceKm'] = '-1'
    assert post('initialize-payment', customer)[0] == 422
    assert post('create-booking', customer)[0] == 422
    assert post('travel-quote', {'location': 'short'})[0] == 410
    print('Local booking endpoint checks passed: totals, payment metadata, invalid-distance rejection.')
finally:
    server.terminate()
    server.wait(timeout=5)
