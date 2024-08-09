from flask import Flask, jsonify, render_template
from flask_cors import CORS
import sqlite3
import logging

app = Flask(__name__)
CORS(app)  # This will enable CORS for all routes

app.logger.disabled = True
log = logging.getLogger('werkzeug')
log.disabled = True

# Function to connect to the SQLite database
def connect_db(db_name='/app/monitor.db'):
    conn = sqlite3.connect(db_name)
    return conn

# Route to fetch data from SQLite and display it
@app.route('/')
def index():
    return render_template('index.html')

# Route to fetch data from SQLite and return it as JSON
@app.route('/data')
def get_data():
    conn = connect_db()
    cursor = conn.cursor()
    cursor.execute('SELECT * FROM monitor')
    rows = cursor.fetchall()
    conn.close()

    data = []
    for row in rows:
        data.append({
            'id': row[0],
            'variable_name': row[1],
            'identifier': row[2],
            'process_name': row[3],
            'protocol': row[4],
            'value': row[5]
        })

    return jsonify(data)

@app.route('/alerts', methods=['GET'])
def get_alerts():
    conn = connect_db()
    cursor = conn.cursor()
    cursor.execute('SELECT id, topic, message, timestamp FROM alerts')
    alerts = cursor.fetchall()
    # convert to dict
    alerts_dict = []
    for alert in alerts:
        alerts_dict.append({
            'id': alert[0],
            'topic': alert[1],
            'message': alert[2],
            'timestamp': alert[3]
        })
    conn.close()
    return jsonify(alerts_dict)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
