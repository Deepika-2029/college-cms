import React from "react";
import "./footer.css";
import { FaGithub, FaLinkedinIn, FaTwitter, FaInstagram } from "react-icons/fa";

export const Footer = () => {
  return (
    <footer className="footer">
      <div className="footer-container">

        {/* Left Section */}
        <div className="footer-left">
          <h2 className="logo">
            Port<span>folio</span>
          </h2>
          <p>Building modern and responsive web experiences.</p>
        </div>

        {/* Center Section */}
        <div className="footer-center">
          <h3>Quick Links</h3>
          <ul>
            <li>Home</li>
            <span>•</span>
            <li>About</li>
            <span>•</span>
            <li>Services</li>
            <span>•</span>
            <li>Projects</li>
            <span>•</span>
            <li>Contact</li>
          </ul>
        </div>

        {/* Right Section */}
        <div className="footer-right">
          <h3>Follow Me</h3>
          <div className="social-icons">
            <a href="https://github.com/harshitbhatt07" target="_blank" rel="noopener noreferrer">
              <div className="icon"><FaGithub /></div>
            </a>
            <a href="https://www.linkedin.com/in/himanshu-bhatt-ba7282395/" target="_blank" rel="noopener noreferrer">
              <div className="icon"><FaLinkedinIn /></div>
            </a>
            <a href="https://x.com/" target="_blank" rel="noopener noreferrer">
              <div className="icon"><FaTwitter /></div>
            </a>
            <a href="https://www.instagram.com/harshit_bhatt_0707/?hl=en" target="_blank" rel="noopener noreferrer">
              <div className="icon"><FaInstagram /></div>
            </a>
          </div>
        </div>

      </div>

      {/* Bottom */}
      <div className="footer-bottom">
        <p>© Developed by Mee.(Himanshu Bhatt). <span>💜</span></p>
      </div>
    </footer>
  );
};
