<?php
/*

Copyright (c) 2009, SilverStripe Australia PTY LTD - www.silverstripe.com.au
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of SilverStripe nor the names of its contributors may be used to endorse or promote products derived from this software
      without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE
GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY
OF SUCH DAMAGE.
*/

/**
 * 
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class LdapContentItem extends ExternalContentItem
{
	/**
	 *
	 * @var Zend_Ldap_Node
	 */
	protected $node;
	
    public function __construct($source=null, $entry=null)
	{
		if ($entry && (is_object($entry) || is_array($entry))) {
			if (is_array($entry)) {
				$entry = Zend_Ldap_Node::fromArray($entry);
			}
			$this->node = $entry;

			$attrs = $entry->getAttributes(false);

			foreach ($attrs as $attr => $value) {
				$this->remoteProperties[$attr] = $value;
			}

			if (isset($this->uid)) {
				$this->Title = is_array($this->uid) ? implode($this->uid) : $this->uid;
			} else if (isset($this->cn)) {
				$this->Title = is_array($this->cn) ? implode($this->cn) : $this->cn;
			} else if (isset($this->ou)) {
				$this->Title = is_array($this->ou) ? implode($this->ou) : $this->ou;
			} else {
				$this->Title = $entry->getCurrentDN();
			}

			$this->DN = $entry->getCurrentDN();

			parent::__construct($source, $entry->getCurrentDn());
		} else {
		    parent::__construct($source, $entry);
		}
	}

	public function stageChildren()
	{
		// get the root, create an object, and get ITs children
		$conn = $this->source->getLdap();
		if ($conn && $conn->countChildren($this->node->getCurrentDN())) {
			// lets do a search for all items that are direct kids
			$items = $conn->search('(objectClass=*)', $this->node->getCurrentDN(), Zend_Ldap::SEARCH_SCOPE_ONE);
			$kids = new DataObjectSet();
			foreach ($items as $item) {
				$item = new LdapContentItem($this->source, $item);
				if ($item->ID) {
					$kids->push($item);
				}
			}
			
			return $kids;
		}
		return new DataObjectSet();
	}

	public function numChildren()
	{
		$conn = $this->source->getLdap();
		if ($conn) {
			return $conn->countChildren($this->node->getCurrentDN());
		}
		return 0;
	}
}
?>