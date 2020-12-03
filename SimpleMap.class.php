<?php
# OpenStreetMap Simple Map - MediaWiki extension
# 
# This defines what happens when <map> tag is placed in the wikitext
# 
# We show a map based on the lat/lon/zoom data passed in. This extension brings in
# image generated by a static map image service.
#
# Usage example:
# <map lat=51.485 lon=-0.15 z=11 w=300 h=200 format=jpeg /> 
#
# Images are not cached local to the wiki.
# To acheive this (remove the OSM dependency) you might set up a squid proxy,
# and modify the requests URLs here accordingly.
#
##################################################################################
#
# Copyright 2008 Harry Wood, Jens Frank, Grant Slater, Raymond Spekking and others
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
# @addtogroup Extensions
#


class SimpleMap {

	function SimpleMap() {
	}

	# The callback function for converting the input text to HTML output
	function parse( $input, $argv ) {
		global $wgScriptPath, $wgKartotherianServiceUrl;

		//Disable in MW 1.21
		//wfLoadExtensionMessages( 'SimpleMap' );
		
		//Receive args of the form <map aaa=bbb ccc=ddd />
		if ( isset( $argv['lat'] ) ) { 
			$lat = $argv['lat'];
		} else {
			$lat = '';
		}
		if ( isset( $argv['lon'] ) ) { 
			$lon = $argv['lon'];
		} else {
			$lon = '';
		}
		if ( isset( $argv['z'] ) ) { 
			$zoom = $argv['z'];
		} else {
			$zoom = '';
		}
		if ( isset( $argv['w'] ) ) { 
			$width = $argv['w'];
		} else {
			$width = '';
		}
		if ( isset( $argv['h'] ) ) { 
			$height	= $argv['h'];
		} else {
			$height = '';
		}
		if ( isset( $argv['format'] ) ) { 
			$format = $argv['format'];
		} else {
			$format	= '';
		}
		if ( isset( $argv['marker'] ) ) { 
			$marker = $argv['marker'];
		} else {
			$marker	= '';
		}

		$error='';

		//default values (meaning these parameters can be missed out)
		if ($width=='')		$width ='450'; 
		if ($height=='')	$height='320'; 
		if ($format=='')	$format='jpeg'; 

		if ($zoom=='') {
			//see if they used 'zoom' rather than 'z' (and allow it)
			if ( isset( $argv['zoom'] ) ) { 
				$zoom = $argv['zoom'];
			}
		}

		
		//trim off the 'px' on the end of pixel measurement numbers (ignore if present)
		if (substr($width,-2)=='px')	$width = (int) substr($width,0,-2);
		if (substr($height,-2)=='px')	$height = (int) substr($height,0,-2);

		$input = trim($input); 	
		if ($input!='') {
			if (strpos($input,'|')!==false) {
				$error = 'Old style tag syntax no longer supported';
			} else {	
				$error = 'slippymap tag contents. Were you trying to input KML? KML support ' .
				         'is disabled pending discussions about wiki syntax<br>';
			}
		}
			
		if ($marker) $error = 'No marker support in the &lt;map&gt; tag extension (yet)';
	
		if ($error=='') {
			//Check required parameters values are provided
			if ( $lat==''  ) $error .= wfMessage( 'simplemap_latmissing' )->text();
			if ( $lon==''  ) $error .= wfMessage( 'simplemap_lonmissing' )->text();
			if ( $zoom=='' ) $error .= wfMessage( 'simplemap_zoommissing' )->text();
			
			//no errors so far. Now check the values	
			if (!is_numeric($width)) {
				$error = wfMessage( 'simplemap_widthnan', $width )->text();
			} else if (!is_numeric($height)) {
				$error = wfMessage( 'simplemap_heightnan', $height )->text();
			} else if (!is_numeric($zoom)) {
				$error = wfMessage( 'simplemap_zoomnan', $zoom )->text();
			} else if (!is_numeric($lat)) {
				$error = wfMessage( 'simplemap_latnan', $lat )->text();
			} else if (!is_numeric($lon)) {
				$error = wfMessage( 'simplemap_lonnan', $lon )->text();
			} else if ($width>1000) {
				$error = wfMessage( 'simplemap_widthbig' )->text();
			} else if ($width<100) {
				$error = wfMessage( 'simplemap_widthsmall' )->text();
			} else if ($height>1000) {
				$error = wfMessage( 'simplemap_heightbig' )->text();
			} else if ($height<100) {
				$error = wfMessage( 'simplemap_heightsmall' )->text();
			} else if ($lat>90) {
				$error = wfMessage( 'simplemap_latbig' )->text();
			} else if ($lat<-90) {
				$error = wfMessage( 'simplemap_latsmall' )->text();
			} else if ($lon>180) {
				$error = wfMessage( 'simplemap_lonbig' )->text();
			} else if ($lon<-180) {
				$error = wfMessage( 'simplemap_lonsmall' )->text();
			} else if ($zoom<0) {
				$error = wfMessage( 'simplemap_zoomsmall' )->text();
			} else if ($zoom==18) {
				$error = wfMessage( 'simplemap_zoom18' )->text();
			} else if ($zoom>17) {
				$error = wfMessage( 'simplemap_zoombig' )->text();
			}
		}

		
		if ($error!="") {
			//Something was wrong. Spew the error message and input text.
			$output  = '';
			$output .= "<span class=\"error\">". wfMessage( 'simplemap_maperror' )->text() . ' ' . $error . "</span><br />";
			$output .= htmlspecialchars($input);
		} else {
			//HTML for the openstreetmap image and link:
			$output  = "";
			$output .= "<a href=\"https://www.openstreetmap.org/?lat=".$lat."&lon=".$lon."&zoom=".$zoom."\" title=\"See this map on OpenStreetMap.org\">";
			$output .= "<img src=\"";
			//$output .= $wgMapOfServiceUrl . "lat=".$lat."&long=".$lon."&z=".$zoom."&w=".$width."&h=".$height."&format=".$format;
			$output .= $wgStaticMapLiteServiceUrl . "center=".$lat.",".$lon."&zoom=".$zoom."&size=".$width."x".$height."&maptype=mapnik";
			//$output .= $wgKartotherianServiceUrl . "osm-intl,".$zoom.",".$lat.",".$lon.",".$width."x".$height.".png";

			$output .= "\" width=\"". $width."\" height=\"".$height."\" border=\"0\">";
			$output .= "</a>";
			
		}
		return $output;
	}
}
