<?php

namespace Maps;
use ValueParsers\StringValueParser;
use ValueParsers\Result;
use ValueParsers\ResultObject;
use ValueParsers\GeoCoordinateParser;
use MWException;

/**
 * ValueParser that parses the string representation of a location.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 3.0
 *
 * @file
 * @ingroup ValueParsers
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LocationParser extends StringValueParser {

	/**
	 * @see StringValueParser::stringParse
	 *
	 * @since 3.0
	 *
	 * @param string $value
	 *
	 * @return Result
	 * @throws MWException
	 */
	public function stringParse( $value ) {
		$separator = '~';

		$metaData = explode( $separator, $value );
		$location = array_shift( $metaData );

		$parser = new GeoCoordinateParser();
		$parseResult = $parser->parse( $location );

		if ( !$parseResult->isValid() ) {
			return ResultObject::newError( $parseResult->getError() );
		}

		$location = new \MapsLocation( $parseResult->getValue() );

		return ResultObject::newSuccess( $location );
	}

}