<html>
<head>
<title>ES6 song assignment 1</title>
</head>
<script>
/*
 Author : Andrew Thwaites
 homework : one
 song object
*/

var title = "The Winnder Takes it All";  // example of a string
var gerne = "Pop";
var artist = "ABBA";
var duration = 210;	// example of an integer	
var is_group = true; // an example of a boolean
var record_label = "polar";
var album = "Super Trouper";
var writer =["B Anderson", "B Ulvaeus"];	// an array
var released = 1980;
var format = ["LP","Cassette","CD","Digital"];
var instruments = ["vocal","drums","piano","drums"]

#var bio = 


// Debug object elements
console.log(title)
console.log(gerne)
console.log(artist)
console.log(duration)
console.log(is_group)
console.log(album)
console.log(released)
console.log(writer)
console.log(record_label)
console.log(format)

console.log(instruments)
console.log(instruments[0])

function Song(title, gerne, artist, duration, is_group, record_label, album, writer, released, format) {
this.title=title;
this.gerne=gerne;
this.artist=artist;
this.duration=duration;
this.is_group= is_group;
this.record_label= record_label;
this.album=album;
this.writer=writer; 
this.released=released;
this.format = format;
}

var track = new Song(title, gerne, artist, duration, is_group, record_label, album, writer, released, format);
console.log(track)

console.log(track.writer[1])
console.log(format.length)
</script>
<body>
<h1>song.js page</h1>
</body>
</html>