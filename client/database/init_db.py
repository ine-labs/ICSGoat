import sqlite3

# Function to connect to the SQLite database
def connect_db(db_name='monitor.db'):
    conn = sqlite3.connect(db_name)
    return conn

# Function to initialize the monitor table
def initialize_db(conn):
    cursor = conn.cursor()
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS monitor (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            variable_name TEXT NOT NULL,
            identifier TEXT,
            process_name TEXT NOT NULL,
            protocol TEXT NOT NULL,
            value TEXT NOT NULL
        )
    ''')
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS alerts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                topic TEXT NOT NULL,
                message TEXT NOT NULL,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
            )
    ''')
    conn.commit()

# Main function to set up the database and table
def main():
    # Connect to the database
    conn = connect_db()

    # Initialize the database
    initialize_db(conn)

    # Close the connection
    conn.close()

if __name__ == '__main__':
    main()
