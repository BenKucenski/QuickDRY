using System;
using System.Collections.Generic;
using System.Net.Mail;
using System.Configuration;

namespace QuickDRY
{
    public class Mailer
    {
        public string to_email;
        public string to_name;
        public string subject;
        public string body;
        public string from_email;
        public string from_name;
        public Dictionary<string, string> attachments;

        public static string DebugEmail;

        public Mailer()
        {

        }
        public Mailer(string to_email, string to_name, string subject, string body, string from_email, string from_name)
        {
            this.to_email = to_email;
            this.to_name = to_name != null && to_name != "null" && to_name != "" ? to_name : to_email;
            this.subject = subject;
            this.body = body;
            this.from_email = from_email;
            this.from_name = from_name;
        }

        public Mailer(string to_email, string to_name, string subject, string body, string from_email, string from_name, Dictionary<string, string> attachments)
        {
            this.to_email = to_email;
            this.to_name = to_name != null && to_name != "null" && to_name != "" ? to_name : to_email;
            this.subject = subject;
            this.body = body;
            this.from_email = from_email;
            this.from_name = from_name;
            this.attachments = attachments;
        }

        public void Send()
        {
            string host = ConfigurationManager.AppSettings["SMTP_IP"];
            int port = Convert.ToInt32(ConfigurationManager.AppSettings["SMTP_PORT"]);

            if (String.IsNullOrEmpty(host) || port == 0)
            {
                return;
            }

            if (DebugEmail != "" && DebugEmail != null)
            {
                to_email = DebugEmail;
                subject = "TEST EMAIL: " + subject;
            }

            if(to_email == "")
            {
                Debug.Halt("No to_email");
            }

            if (from_email == "")
            {
                Debug.Halt("No from_email");
            }
            to_name = to_name != "null" && to_name != "" ? to_name : to_email;

            MailAddress To = new MailAddress(to_email, to_name);
            MailAddress From = new MailAddress(from_email, from_name);

            MailMessage msg = new MailMessage(From, To);
            msg.Subject = subject;
            msg.Body = body;
            msg.IsBodyHtml = true;

            if (attachments != null)
            {
                foreach (string filename in attachments.Keys)
                {
                    string name = attachments[filename];
                    Attachment a = new Attachment(filename);
                    a.Name = name;
                    msg.Attachments.Add(a);
                }
            }


            SmtpClient client = new SmtpClient(host, port);
            client.EnableSsl = false;
            client.DeliveryMethod = SmtpDeliveryMethod.Network;
            client.Send(msg);
        }

        public static List<String> BCCToList(String Bcc)
        {
            List<string> Bccs = new List<string>();
            if (Bcc.Contains(","))
            {
                string[] e = Bcc.Split(',');
                foreach (string em in e)
                {
                    Bccs.Add(em.Trim());
                }
            }
            else
            if (Bcc.Contains(";"))
            {
                string[] e = Bcc.Split(';');
                foreach (string em in e)
                {
                    Bccs.Add(em.Trim());
                }
            }
            else
            {
                Bccs.Add(Bcc);
            }
            return Bccs;
        }
    }
}
