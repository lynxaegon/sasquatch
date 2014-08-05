#!/usr/bin/env python
# requirements:
#
# Pyhton 2.7.3 (on 2.7.2 there is a deadlock on popen)
#
# sudo apt-get install python-mysqldb
# pip install croniter

import sys
import os
import select
import errno
import shlex, subprocess
import MySQLdb
import contextlib
from Queue import Queue
from threading import Thread
import threading
import fcntl
from croniter import croniter
from datetime import datetime
import time
import json
import hashlib
from warnings import filterwarnings
filterwarnings('ignore', category = MySQLdb.Warning)

queue = Queue()

class Runner:
	def __init__(self,cronID,command, q):
		self.q = q
		self.runID = hashlib.md5(datetime.now().strftime("%s") + "-"+ str(cronID)).hexdigest()
		self.cronID = cronID
		self.command = command

	def run(self):
		args = shlex.split(self.command)
		p = subprocess.Popen(args,stdout=subprocess.PIPE, stderr=subprocess.PIPE)

		self.make_async(p.stdout)
		self.make_async(p.stderr)
		
		while True:
			select.select([p.stdout, p.stderr], [], [])

			stdoutPiece = self.read_async(p.stdout).rstrip()
			stderrPiece = self.read_async(p.stderr).rstrip()


			if stdoutPiece:
				self.q.put({"runID": self.runID, "output" : stdoutPiece, "type" : "stdout"})

			if stderrPiece:
				self.q.put({"runID": self.runID, "output" : stderrPiece, "type" : "stderr"})

			if(p.poll() != None):
				self.q.put({"cronID" : self.cronID, "runID": self.runID, "type" : "finished"})
				break
			
	def make_async(self,fd):
	    fcntl.fcntl(fd, fcntl.F_SETFL, fcntl.fcntl(fd, fcntl.F_GETFL) | os.O_NONBLOCK)
		    
	def read_async(self,fd):
	    try:
	        return fd.read()
	    except IOError, e:
	        if e.errno != errno.EAGAIN:
	            raise e
	        else:
	            return ''

class Config:
	def __init__(self):
		script_location = os.path.split(os.path.abspath(os.path.realpath(sys.argv[0])))[0]

		configFile = json_data=open(script_location + "/config.json")
		configData = json.load(configFile);

		self.ENVIROMENT = configData['ENVIROMENT']
		self.DB_HOST = configData['DB_HOST']
		self.DB_USER = configData['DB_USER']
		self.DB_PASS = configData['DB_PASS']
		self.DB_DB = configData['DB_DB']
        	
def mysqlWriter(q, db):
	cursor = db.cursor()
	while True:
		data = q.get()
		
		if(data == None):
			break;
		if(data['type'] == "finished"):
			cursor.execute("UPDATE crons SET lastDuration = UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(lastRunTime), isRunning = 0  WHERE ID = " + str(data['cronID']))
			cursor.execute("UPDATE cron_runs SET endDateTime = NOW() WHERE runID = '" + data['runID'] + "'")
			db.commit()
			continue
		# print data
		cursor.execute("INSERT INTO logger (runID, output, type, dateTimeAdded) VALUES('" + data['runID'] + "','" + MySQLdb.escape_string(data['output']) + "','" + MySQLdb.escape_string(data['type']) + "',NOW() )")
		db.commit()

	cursor.close()

config = Config()


db = MySQLdb.connect(host=config.DB_HOST,
                     user=config.DB_USER,
                     passwd=config.DB_PASS,
                     db=config.DB_DB)
cursor = db.cursor(cursorclass=MySQLdb.cursors.DictCursor)

cursor.execute("SELECT * FROM crons WHERE isActive = 1")


appThreads = []

now = datetime.now()
data = cursor.fetchall()
for item in data :
	if(item['lastRunTime'] == None):
		cursor.execute("UPDATE crons SET lastRunTime = NOW() WHERE ID = " + str(item['ID']))
		db.commit()
	else:
		if(item['cronPattern'] == ""):
			continue

		cron = croniter(item['cronPattern'], item['lastRunTime'])
		shouldRunAt = cron.get_next(datetime)

		if(shouldRunAt < now):
			app = Runner(item['ID'], item['command'], queue)

			cursor.execute("UPDATE crons SET lastRunTime = NOW(), isRunning = 1 WHERE ID = " + str(item['ID']))
			cursor.execute("INSERT INTO cron_runs (runID, cronID, startDateTime) VALUES( '" + app.runID + "', '" + str(app.cronID) + "', NOW() )")
			db.commit()     

			th = Thread(target=app.run)
			th.daemon = True
			appThreads.append(th)

cursor.close()

worker = Thread(target=mysqlWriter, args=(queue,db))
worker.daemon = True
worker.start()

[x.start() for x in appThreads]
[x.join() for x in appThreads]

queue.put(None)

worker.join()

db.close()