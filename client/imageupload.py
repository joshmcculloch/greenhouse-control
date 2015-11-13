#!/usr/bin/python

#from SimpleCV import Camera, Image
import time
import requests
from subprocess import call
#camera = Camera()
#image = camera.getImage()
#time.sleep(5)
while True:
	#image = camera.getImage()
	
	#image.save("camera.jpg")
	call(["fswebcam","-S", "30","-r","640x480", "-F", "10", "camera.jpg"])

	url = "http://green.joshmcculloch.nz/camera_upload.php"
	files = {"file": open("camera.jpg", "rb")}
	r = requests.post(url, files=files)
	print(r.text)
	time.sleep(30)
