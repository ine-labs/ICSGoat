# mqtt server
import asyncio
from hbmqtt.broker import Broker

config = {
    'listeners': {
        'default': {
            'type': 'tcp',
            'bind': '127.0.0.1:1883',
        },
    },
    'sys_interval': 10,
    'auth': {
        'allow-anonymous': True,
    },
}

broker = Broker(config)

@asyncio.coroutine
def test_coro():
    yield from broker.start()

if __name__ == '__main__':
    asyncio.get_event_loop().run_until_complete(start_broker())
    asyncio.get_event_loop().run_forever()
