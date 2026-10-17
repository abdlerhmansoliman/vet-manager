import socket
import threading

def handle_client(client_sock):
    try:
        remote_sock = socket.create_connection(('127.0.0.1', 3306))
    except Exception:
        client_sock.close()
        return

    def forward(source, destination):
        try:
            while True:
                data = source.recv(65536)
                if not data:
                    break
                destination.sendall(data)
        except Exception:
            pass
        finally:
            try:
                source.close()
            except Exception:
                pass
            try:
                destination.close()
            except Exception:
                pass

    t1 = threading.Thread(target=forward, args=(client_sock, remote_sock), daemon=True)
    t2 = threading.Thread(target=forward, args=(remote_sock, client_sock), daemon=True)
    t1.start()
    t2.start()

def main():
    server = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    server.bind(('172.17.0.1', 3306))
    server.listen(128)
    print("MySQL bridge listening on 172.17.0.1:3306 -> 127.0.0.1:3306", flush=True)

    while True:
        client, _ = server.accept()
        threading.Thread(target=handle_client, args=(client,), daemon=True).start()

if __name__ == '__main__':
    main()
