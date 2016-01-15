#!/usr/bin/python

import time
import requests
from subprocess import call

def capture_image(device="/dev/video0", img_no=1):
	call(["fswebcam","-S", "30","-r","640x480", "--no-banner", "-d", device, "-F", "1", "camera.jpg"])

	url = "http://sprouter.info/camera_upload.php?camera=%d"%img_no
	files = {"file": open("camera.jpg", "rb")}
	return requests.post(url, files=files).text


while True:
	print("Camera 1:",capture_image("/dev/video0",1))
	print("Camera 2:",capture_image("/dev/video1",2))
	print("Camera 3:",capture_image("/dev/video2",3))

	time.sleep(30)

